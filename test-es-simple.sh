#!/bin/bash

# Simple Elasticsearch Test - No Laravel needed
# Direct API calls to Elasticsearch

ES_HOST="https://api-dev-wellmed.kalpahealth.com/elastic-dev"
ES_USER="elastic"
ES_PASS="password"

# Try different tenant IDs
TENANT_IDS=("4" "3" "2")

echo "=== Elasticsearch Direct Test (No Laravel) ==="
echo ""

# Find which index exists
echo "Step 1: Finding active index..."
for TENANT_ID in "${TENANT_IDS[@]}"; do
    INDEX_NAME="local.${TENANT_ID}.visit_registration"
    echo -n "Checking $INDEX_NAME ... "

    RESPONSE=$(curl -s -k -u "$ES_USER:$ES_PASS" -o /dev/null -w "%{http_code}" "$ES_HOST/$INDEX_NAME")

    if [ "$RESPONSE" == "200" ]; then
        echo "✓ EXISTS"
        ACTIVE_INDEX=$INDEX_NAME
        ACTIVE_TENANT=$TENANT_ID
        break
    else
        echo "✗ Not found"
    fi
done

if [ -z "$ACTIVE_INDEX" ]; then
    echo ""
    echo "❌ No visit_registration index found!"
    echo "Trying to list all indices..."
    curl -s -k -u "$ES_USER:$ES_PASS" "$ES_HOST/_cat/indices?v" | grep visit
    exit 1
fi

echo ""
echo "Using index: $ACTIVE_INDEX"
echo "=========================================="
echo ""

# Step 2: Get total documents
echo "Step 2: Total Documents"
TOTAL=$(curl -s -k -u "$ES_USER:$ES_PASS" "$ES_HOST/$ACTIVE_INDEX/_count" | grep -o '"count":[0-9]*' | cut -d':' -f2)
echo "Total documents: $TOTAL"
echo ""

if [ "$TOTAL" == "0" ]; then
    echo "⚠️  No documents in index!"
    exit 0
fi

# Step 3: Get sample document
echo "Step 3: Sample Document"
curl -s -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$ACTIVE_INDEX/_search" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {"match_all": {}},
    "size": 1
  }' | python3 -m json.tool | head -50
echo ""
echo "=========================================="
echo ""

# Step 4: Test status filter
echo "Step 4: Count documents with status=COMPLETED"
STATUS_TOTAL=$(curl -s -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$ACTIVE_INDEX/_search" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {
      "term": {"status": "COMPLETED"}
    },
    "size": 0
  }' | grep -o '"value":[0-9]*' | head -1 | cut -d':' -f2)
echo "Documents with status=COMPLETED: $STATUS_TOTAL"
echo ""

# Step 5: Test random string (SHOULD BE 0)
echo "Step 5: Search random string (should be 0)"
RANDOM_STR="Hamzahshdfsdfsdfsdadgdgadsgf"
RANDOM_TOTAL=$(curl -s -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$ACTIVE_INDEX/_search" \
  -H 'Content-Type: application/json' \
  -d "{
    \"query\": {
      \"bool\": {
        \"should\": [
          {\"match\": {\"patient_name\": \"$RANDOM_STR\"}},
          {\"match\": {\"patient_nik\": \"$RANDOM_STR\"}},
          {\"match\": {\"patient_id\": \"$RANDOM_STR\"}}
        ],
        \"minimum_should_match\": 1
      }
    },
    \"size\": 0
  }" | grep -o '"value":[0-9]*' | head -1 | cut -d':' -f2)

echo "Search '$RANDOM_STR': $RANDOM_TOTAL results"
echo "Expected: 0"
if [ "$RANDOM_TOTAL" != "0" ]; then
    echo "⚠️  WARNING: Found matches for random string!"
else
    echo "✅ Correct: No matches"
fi
echo ""

# Step 6: Test HYBRID query (random + status) - SHOULD BE 0
echo "Step 6: HYBRID Query (random string + status=COMPLETED)"
echo "Expected: 0 (because random string matches nothing)"
HYBRID_TOTAL=$(curl -s -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$ACTIVE_INDEX/_search" \
  -H 'Content-Type: application/json' \
  -d "{
    \"query\": {
      \"bool\": {
        \"must\": [
          {
            \"bool\": {
              \"should\": [
                {\"match\": {\"patient_name\": \"$RANDOM_STR\"}},
                {\"match\": {\"patient_nik\": \"$RANDOM_STR\"}},
                {\"match\": {\"patient_id\": \"$RANDOM_STR\"}}
              ],
              \"minimum_should_match\": 1
            }
          },
          {
            \"term\": {\"status\": \"COMPLETED\"}
          }
        ]
      }
    },
    \"size\": 0
  }" | grep -o '"value":[0-9]*' | head -1 | cut -d':' -f2)

echo "HYBRID query result: $HYBRID_TOTAL"
if [ "$HYBRID_TOTAL" != "0" ]; then
    echo "❌ BUG: Should be 0 but got $HYBRID_TOTAL!"
else
    echo "✅ Correct: Hybrid query working!"
fi
echo ""

echo "=========================================="
echo "Summary:"
echo "- Total docs: $TOTAL"
echo "- Status=COMPLETED: $STATUS_TOTAL"
echo "- Random string only: $RANDOM_TOTAL (expected: 0)"
echo "- Hybrid (random+status): $HYBRID_TOTAL (expected: 0)"
echo ""

if [ "$RANDOM_TOTAL" == "0" ] && [ "$HYBRID_TOTAL" == "0" ]; then
    echo "✅ Elasticsearch queries working correctly!"
    echo "   The bug might be in the application code."
else
    echo "❌ Elasticsearch returning unexpected results!"
fi
