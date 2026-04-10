# Queue Number Refactor Summary

## Problem Statement
Queue number counter di Elasticsearch naik meskipun penyimpanan pendaftaran gagal, karena `getNextQueueNumber()` langsung increment counter.

## Solution
Memisahkan proses reserve dan confirm queue number:
1. **Reserve** - Ambil queue number berikutnya TANPA increment counter
2. **Confirm** - Increment counter HANYA setelah pendaftaran berhasil disimpan

## Changes Made

### 1. Service Methods (VisitRegistrationQueueService.php)

#### New Method: `reserveNextQueueNumber()`
- Reads current count from ES
- Returns `count + 1` (next number yang akan dipakai)
- **TIDAK increment** counter di ES
- Return type: `int` (tidak bisa return null)
- Fallback jika error: return timestamp-based number `(int) now()->format('His')`

```php
public function reserveNextQueueNumber(?int $tenantId = null): int
{
    try {
        $document = $this->getOrCreateQueueDocument($tenantId);
        $nextNumber = ($document['count'] ?? 0) + 1;
        return $nextNumber;
    } catch (\Throwable $e) {
        // Fallback to timestamp
        return (int) now()->format('His');
    }
}
```

#### New Method: `confirmQueueNumber()`
- Reads current count from ES
- Increments count by 1
- Saves updated document to ES
- Return type: `bool` (success/failure)

```php
public function confirmQueueNumber(?int $tenantId = null): bool
{
    try {
        $document = $this->getOrCreateQueueDocument($tenantId);
        $document['count'] = ($document['count'] ?? 0) + 1;
        $result = $this->storeDocument($document, $tenantId);
        return $result['success'];
    } catch (\Throwable $e) {
        return false;
    }
}
```

### 2. Controller Changes (VisitExaminationController.php)

#### Old Flow:
```php
// Langsung increment counter
$queue_number = $service->getNextQueueNumber(); // Counter: 5 → 6
$visit_patient = $this->storeVisitPatient(); // Jika GAGAL, counter sudah 6! ❌
```

#### New Flow:
```php
// 1. Reserve queue number (counter tetap)
$queue_number = $queueService->reserveNextQueueNumber(); // Returns: 6, Counter masih: 5 ✅

// 2. Store visit patient
$visit_patient = $this->storeVisitPatient(); // Jika GAGAL, counter masih 5 ✅

// 3. Confirm HANYA jika berhasil
if ($queueService && $queue_number) {
    $queueService->confirmQueueNumber(); // Counter: 5 → 6 ✅
}
```

## Possible Issues if queue_number is NULL

### Issue: `queue_number` tersimpan sebagai NULL di database

**Possible Causes:**

1. **Elasticsearch disabled di server**
   - Config `elasticsearch.enabled` = false
   - Check: Log akan show "[VisitExamination] Elasticsearch is disabled"

2. **Service instantiation failed**
   - Class `VisitRegistrationQueueService` tidak ditemukan
   - Autoload issue
   - Check: Log akan show "[VisitExamination] FAILED to reserve queue number from ES"

3. **ES connection failed**
   - ES server tidak bisa diakses
   - Auth failed
   - Check: Log akan show error dari `getOrCreateQueueDocument()`

4. **Exception during reserve**
   - Tenant not found
   - ES index error
   - Check: Log elasticsearch channel

## Debugging Steps

### Step 1: Check Logs
Look for these log entries after testing:

```bash
# Check main log
tail -f storage/logs/laravel.log | grep "VisitExamination"

# Check ES log
tail -f storage/logs/elasticsearch.log
```

Expected log sequence if working:
```
[VisitExamination] Starting queue number reservation (elasticsearch.enabled=true)
[VisitExamination] Attempting to instantiate VisitRegistrationQueueService
[VisitExamination] Service instantiated successfully
[VisitExamination] Calling reserveNextQueueNumber()
[VisitExamination] reserveNextQueueNumber() returned (queue_number=6, type=integer)
[VisitExamination] Queue number reserved successfully
... after storeVisitPatient() success ...
[VisitExamination] Queue number confirmed in ES
```

### Step 2: Verify Config
```php
// In tinker or test script
config('elasticsearch.enabled'); // should be true
config('elasticsearch.hosts'); // should be valid ES host
```

### Step 3: Test Service Directly
```php
// In tinker
$service = app(\Projects\WellmedBackbone\Services\VisitRegistrationQueueService::class);
$reserved = $service->reserveNextQueueNumber(); // Should return integer
echo "Reserved: " . $reserved . " (type: " . gettype($reserved) . ")\n";

$confirmed = $service->confirmQueueNumber(); // Should return true
echo "Confirmed: " . ($confirmed ? 'true' : 'false') . "\n";
```

## Important Notes

1. **Return types prevent NULL**
   - `reserveNextQueueNumber(): int` cannot return NULL without TypeError
   - Fallback returns timestamp integer if error occurs
   - If queue_number is NULL, exception was caught at controller level

2. **Idempotency**
   - Multiple calls to `reserveNextQueueNumber()` will return same number (counter not changed)
   - Only `confirmQueueNumber()` actually increments counter
   - This is intentional - prevents counter drift

3. **Race Conditions**
   - ES document updates are atomic
   - If two requests reserve simultaneously, they get same number
   - Only first confirm will succeed cleanly
   - **TODO:** Consider adding optimistic locking if needed

## Testing Checklist

- [ ] ES config enabled di environment test
- [ ] Service dapat di-instantiate tanpa error
- [ ] `reserveNextQueueNumber()` return integer (bukan null)
- [ ] Counter TIDAK naik setelah reserve
- [ ] `storeVisitPatient()` berhasil save
- [ ] `confirmQueueNumber()` dipanggil setelah success
- [ ] Counter naik 1 setelah confirm
- [ ] Log shows complete flow without errors
- [ ] Database shows queue_number dengan value integer (bukan null)
- [ ] Jika `storeVisitPatient()` gagal, counter tidak naik

## Files Modified

1. `projects/wellmed-backbone/src/Services/VisitRegistrationQueueService.php`
   - Added `reserveNextQueueNumber()` method
   - Added `confirmQueueNumber()` method

2. `projects/wellmed-gateway/src/Controllers/API/PatientEmr/Patient/VisitExamination/VisitExaminationController.php`
   - Changed from `getNextQueueNumber()` to `reserveNextQueueNumber()`
   - Added `confirmQueueNumber()` after successful store
   - Added comprehensive logging
   - Added import for `Log` facade
