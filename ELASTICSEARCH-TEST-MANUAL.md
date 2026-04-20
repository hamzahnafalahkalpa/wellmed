# Manual Elasticsearch Testing Guide

Karena ES endpoint tidak bisa diakses langsung dari CLI, gunakan **Postman** atau **Browser DevTools** untuk test.

## Setup
- **Base URL**: `https://api-dev-wellmed.kalpahealth.com/elastic-dev`
- **Auth**: Basic Auth
  - Username: `elastic`
  - Password: `password`
- **Headers**: `Content-Type: application/json`

---

## Test 1: Find Active Index

**Request:**
```
GET https://api-dev-wellmed.kalpahealth.com/elastic-dev/_cat/indices?v
```

Look for indices matching pattern: `local.*.visit_registration`

Example: `local.4.visit_registration`

---

## Test 2: Count Total Documents

Replace `{INDEX}` with actual index name (e.g., `local.4.visit_registration`)

**Request:**
```
GET https://api-dev-wellmed.kalpahealth.com/elastic-dev/{INDEX}/_count
```

**Expected Response:**
```json
{
  "count": 150,
  "_shards": {...}
}
```

---

## Test 3: Get Sample Document

**Request:**
```
POST https://api-dev-wellmed.kalpahealth.com/elastic-dev/{INDEX}/_search
```

**Body:**
```json
{
  "query": {
    "match_all": {}
  },
  "size": 1
}
```

**Look for in response:**
- `hits.total.value` - total documents
- `hits.hits[0]._source` - sample document with fields like `patient_name`, `status`, etc.

---

## Test 4: Filter by Status (Should Return Results)

**Request:**
```
POST https://api-dev-wellmed.kalpahealth.com/elastic-dev/{INDEX}/_search
```

**Body:**
```json
{
  "query": {
    "term": {
      "status": "COMPLETED"
    }
  },
  "size": 0
}
```

**Expected:** `hits.total.value` > 0

---

## Test 5: Random String Search (Should Return 0)

**Request:**
```
POST https://api-dev-wellmed.kalpahealth.com/elastic-dev/{INDEX}/_search
```

**Body:**
```json
{
  "query": {
    "bool": {
      "should": [
        {"match": {"patient_name": "Hamzahshdfsdfsdfsdadgdgadsgf"}},
        {"match": {"patient_nik": "Hamzahshdfsdfsdfsdadgdgadsgf"}},
        {"match": {"patient_id": "Hamzahshdfsdfsdfsdadgdgadsgf"}}
      ],
      "minimum_should_match": 1
    }
  },
  "size": 0
}
```

**Expected:** `hits.total.value` = 0

---

## Test 6: HYBRID Query (Should Return 0)

**This is the CRITICAL test!**

**Request:**
```
POST https://api-dev-wellmed.kalpahealth.com/elastic-dev/{INDEX}/_search
```

**Body:**
```json
{
  "query": {
    "bool": {
      "must": [
        {
          "bool": {
            "should": [
              {"match": {"patient_name": "Hamzahshdfsdfsdfsdadgdgadsgf"}},
              {"match": {"patient_nik": "Hamzahshdfsdfsdfsdadgdgadsgf"}},
              {"match": {"patient_id": "Hamzahshdfsdfsdfsdadgdgadsgf"}}
            ],
            "minimum_should_match": 1
          }
        },
        {
          "term": {
            "status": "COMPLETED"
          }
        }
      ]
    }
  },
  "size": 10
}
```

**Expected:** `hits.total.value` = 0

**If > 0:** ❌ BUG - Elasticsearch is returning results when it shouldn't!

**If = 0:** ✅ Elasticsearch working correctly - bug is in application code!

---

## Diagnosis

### If Test 6 returns 0:
The bug is in the **application code**:
- Check if `setParamLogic()` is properly detecting explicit fields
- Check if query builder is creating the correct structure
- Check logs in `storage/logs/laravel.log` for `[SearchDebug]`

### If Test 6 returns > 0:
The bug is in **Elasticsearch data or query structure**:
- The query structure is wrong
- Or data in ES is malformed
- Check what fields actually matched

---

## Next Steps

1. Run Test 1 to find the index name
2. Run Tests 2-6 in order
3. Note the `hits.total.value` for each test
4. Share results for analysis
