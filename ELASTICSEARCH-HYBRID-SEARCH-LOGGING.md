# Elasticsearch Hybrid Search - Comprehensive Logging

## Overview

Logging yang comprehensive telah ditambahkan ke `elasticsearch.log` untuk debug **Hybrid Search Mode** di Elasticsearch.

## Hybrid Search Mode - Konsep

Hybrid search adalah kombinasi OR dan AND operators:

```
(search_value expanded ke beberapa field dengan OR)
AND
(explicit filters dengan AND)
```

### Example Query dari Frontend

```
?search_value=Remon&search_status=COMPLETED&search_created_at=2026-04-20
```

### Transformasi Menjadi Elasticsearch Query

```javascript
{
  "query": {
    "bool": {
      "must": [
        // OR GROUP - dari search_value expansion
        {
          "bool": {
            "should": [
              { "match": { "patient_name": "Remon" }},
              { "match": { "patient_nik": "Remon" }},
              { "match": { "patient_id": "Remon" }},
              { "match": { "medic_service_name": "Remon" }},
              // ... semua field kecuali yang explicit
            ],
            "minimum_should_match": 1
          }
        },
        // AND FILTERS - explicit dari request
        { "term": { "status": "COMPLETED" }},
        { "range": { "created_at": { "gte": "2026-04-20T00:00:00Z", "lte": "2026-04-20T23:59:59Z" }}}
      ]
    }
  }
}
```

**Artinya:**
- Data harus match minimal 1 dari OR group (patient_name, patient_nik, dll)
- DAN status harus COMPLETED
- DAN created_at harus 2026-04-20

Jika `search_value=askdjfbnjkasdbfjkadsbfkadsbjkfadsb` (random string), **harusnya return 0 results** karena tidak ada field yang match di OR group.

---

## Files Yang Dimodifikasi

### 1. `repositories/laravel-support/src/Concerns/Support/HasElasticSearch.php`

**Changes:**
- Semua logging sekarang menggunakan `Log::channel('elasticsearch')`
- Fixed `match_none` query structure untuk return no results di hybrid mode
- Added comprehensive logging di setiap stage:
  - `[HYBRID MODE]` - Hybrid mode detection & query building
  - `[QUERY BUILD]` - Query structure building
  - `[ES QUERY]` - Query execution ke Elasticsearch
  - `[ES RESPONSE]` - Response dari Elasticsearch
  - `[SCOPE]` - Scope execution
  - `[ES ERROR]` - Error handling
  - `[CIRCUIT BREAKER]` - Circuit breaker status
- Added tracking untuk skipped fields (date fields dengan invalid values)

**Key Fix:**
```php
// BEFORE: might not work correctly
return [
    'query' => [
        'bool' => [
            'must_not' => [
                'match_all' => new \stdClass()
            ]
        ]
    ]
];

// AFTER: proper Elasticsearch syntax
return [
    'query' => [
        'match_none' => new \stdClass()
    ]
];
```

### 2. `repositories/laravel-support/src/Concerns/PackageManagement/DataManagement.php`

**Changes:**
- `setParamLogic()` logging menggunakan `Log::channel('elasticsearch')`
- Added `[PARAM LOGIC]` prefix untuk semua logs
- More detailed logging untuk hybrid mode detection
- Logs includes:
  - search_value
  - explicit_fields yang terdeteksi
  - expanded_fields (hasil expansion search_value)
  - hybrid_mode status (YES/NO)
  - metadata yang di-set (`__explicit_search_fields`)

---

## Log Structure di `storage/logs/elasticsearch.log`

### 1. Param Logic Expansion

```log
[PARAM LOGIC] search_value expansion completed
{
  "entity": "VisitRegistration",
  "search_value": "Remon",
  "explicit_fields": ["status", "created_at"],
  "expanded_fields": ["patient_name", "patient_nik", "patient_id", ...],
  "expanded_count": 8,
  "total_params": 12,
  "param_logic": "or",
  "hybrid_mode": "YES",
  "metadata_set": "status,created_at"
}
```

### 2. Scope Execution

```log
[SCOPE] Building ES query
{
  "model": "Projects\\WellmedBackbone\\Models\\ModulePatient\\EMR\\VisitRegistration",
  "operator": "or",
  "search_params": ["search_patient_name", "search_patient_nik", "search_status", "search_created_at", ...],
  "has_explicit_fields": true,
  "explicit_fields": "status,created_at"
}
```

### 3. Hybrid Mode Query Building

```log
[HYBRID MODE] Query built successfully
{
  "model": "...",
  "explicit_fields": ["status", "created_at"],
  "or_clauses_count": 8,
  "and_clauses_count": 2,
  "must_clauses_count": 3,
  "query_structure": {
    "OR_group_fields": "8 fields",
    "AND_filters": ["status", "created_at"],
    "total_must_clauses": 3
  },
  "full_query": "{...}" // Pretty-printed Elasticsearch query
}
```

### 4. Skipped Fields (jika ada)

```log
[HYBRID MODE] Some fields skipped during query build
{
  "model": "...",
  "skipped_count": 2,
  "skipped_fields": [
    {"field": "created_at", "reason": "invalid_date_value", "cast": "date", "value": "Remon"},
    {"field": "warehouse_id", "reason": "empty_value"}
  ]
}
```

### 5. Empty OR Clauses (Bug Detection)

```log
[HYBRID MODE] Empty OR clauses - returning NO results
{
  "model": "...",
  "explicit_fields": ["status", "created_at"],
  "and_clauses_count": 2,
  "parameters": ["search_patient_name", "search_patient_nik", ...],
  "reason": "search_value expanded to no valid fields (all skipped or excluded)"
}
```

### 6. ES Query Execution

```log
[ES QUERY] Executing search
{
  "model": "...",
  "index": "local.4.visit_registration",
  "page": 1,
  "size": 10,
  "query": "{...}" // Pretty-printed query
}
```

### 7. ES Response

```log
[ES RESPONSE] Search completed
{
  "model": "...",
  "index": "local.4.visit_registration",
  "total_matches": 0,
  "returned_hits": 0,
  "took_ms": 5
}
```

### 8. Scope Query Completion

```log
[SCOPE] Query execution completed
{
  "model": "...",
  "total_matches": 0,
  "ids_retrieved": 0,
  "es_time_ms": 5.23,
  "has_error": false
}
```

---

## Testing Procedure

### 1. Start Docker Container

```bash
docker-compose -f docker-compose-dev.yaml up -d wellmed-backbone
```

### 2. Clear Cache & Reload Octane

```bash
docker exec -it wellmed-backbone php artisan config:clear
docker exec -it wellmed-backbone php artisan cache:clear
docker exec -it wellmed-backbone php artisan octane:reload
```

### 3. Test Case 1: Valid Search

**Request:**
```
GET /api/visit-registrations?search_value=Remon&search_status=COMPLETED&search_created_at=2026-04-20
```

**Expected Logs:**
```log
[PARAM LOGIC] search_value expansion completed
  → explicit_fields: ["status", "created_at"]
  → expanded_fields: ["patient_name", "patient_nik", ...]
  → hybrid_mode: "YES"

[HYBRID MODE] Query built successfully
  → or_clauses_count: 8
  → and_clauses_count: 2

[ES RESPONSE] Search completed
  → total_matches: 5 (atau berapa yang sesuai data)
```

### 4. Test Case 2: Random Search Value (Bug Check)

**Request:**
```
GET /api/visit-registrations?search_value=askdjfbnjkasdbfjkadsbfkadsbjkfadsb&search_status=COMPLETED&search_created_at=2026-04-20
```

**Expected Logs:**
```log
[PARAM LOGIC] search_value expansion completed
  → search_value: "askdjfbnjkasdbfjkadsbfkadsbjkfadsb"
  → expanded_fields: ["patient_name", "patient_nik", ...]

[HYBRID MODE] Some fields skipped during query build (optional)
  → skipped_fields: [date fields with invalid value]

[HYBRID MODE] Query built successfully
  → or_clauses_count: 8 (atau kurang jika ada yang di-skip)
  → and_clauses_count: 2

[ES RESPONSE] Search completed
  → total_matches: 0 ✅ (EXPECTED: should be 0)
```

**Jika `total_matches` > 0**, itu adalah bug! Check full_query di log untuk analyze.

### 5. Test Case 3: No Explicit Filters (Pure OR)

**Request:**
```
GET /api/visit-registrations?search_value=Remon
```

**Expected Logs:**
```log
[PARAM LOGIC] search_value expansion completed
  → explicit_fields: []
  → hybrid_mode: "NO"

[QUERY BUILD] Standard OR mode
  → or_clauses_count: 10 (all cast fields)

[ES RESPONSE] Search completed
  → total_matches: X (whatever matches)
```

---

## Debugging Workflow

### Jika Random Search Value Masih Return Results

1. **Check PARAM LOGIC log:**
   ```bash
   docker exec -it wellmed-backbone tail -f storage/logs/elasticsearch.log | grep "PARAM LOGIC"
   ```

   - Ensure `explicit_fields` terdeteksi correctly (status, created_at)
   - Ensure `expanded_fields` tidak include explicit_fields
   - Ensure `hybrid_mode: "YES"`

2. **Check HYBRID MODE log:**
   ```bash
   docker exec -it wellmed-backbone tail -f storage/logs/elasticsearch.log | grep "HYBRID MODE"
   ```

   - Check `or_clauses_count` - harus > 0
   - Check `full_query` - structure harus benar:
     ```json
     {
       "query": {
         "bool": {
           "must": [
             { "bool": { "should": [...], "minimum_should_match": 1 }},
             { "term": { "status": "COMPLETED" }},
             { "range": { "created_at": {...} }}
           ]
         }
       }
     }
     ```

3. **Check ES RESPONSE:**
   ```bash
   docker exec -it wellmed-backbone tail -f storage/logs/elasticsearch.log | grep "ES RESPONSE"
   ```

   - Jika `total_matches: 0` ✅ → Working correctly
   - Jika `total_matches: > 0` ❌ → Bug detected!

4. **Manual Test ke Elasticsearch:**
   - Copy `full_query` dari log
   - Test langsung ke Elasticsearch (lihat ELASTICSEARCH-TEST-MANUAL.md)
   - Jika Elasticsearch return 0 → bug di application code
   - Jika Elasticsearch return > 0 → bug di query structure

---

## Configuration

Logging channel sudah configured di `config/logging.php`:

```php
'elasticsearch' => [
    'driver' => 'single',
    'path' => storage_path('logs/elasticsearch.log'),
    'level' => 'info',
],
```

Untuk enable debug level (more verbose):

```php
'level' => 'debug',
```

---

## Performance Notes

- Logging menggunakan JSON_PRETTY_PRINT untuk full_query agar mudah dibaca
- Skipped fields hanya di-log di debug level untuk avoid log spam
- Circuit breaker logs di-log sebagai warning untuk monitoring

---

## Next Steps

1. **Remove `setParamLogic('and')` dari controller** jika belum:
   ```php
   // projects/wellmed-gateway/src/Controllers/API/PatientEmr/VisitRegistration/EnvironmentController.php

   // REMOVE or COMMENT OUT:
   // ->setParamLogic('and')
   ```

2. **Test dengan scenario di atas**

3. **Analyze logs** untuk understand bug

4. **Share hasil test** untuk further debugging jika masih ada issue

---

## Known Issues & Fixes

### Issue 1: OR Clauses Empty di Hybrid Mode
**Symptom:** Random search_value masih return results
**Fix Applied:** Changed from `must_not: match_all` to `match_none` query
**Location:** `HasElasticSearch.php:259-277`

### Issue 2: Date Fields Causing OR Clauses to Empty
**Symptom:** All OR fields skipped because search_value is not valid date
**Detection:** Check `[HYBRID MODE] Some fields skipped` log
**Solution:** This is expected behavior - if search_value can't match any field type, should return 0 results

---

## Contact & Support

For issues or questions:
1. Check logs di `storage/logs/elasticsearch.log`
2. Share relevant log entries (PARAM LOGIC, HYBRID MODE, ES RESPONSE)
3. Include request parameters yang di-test
