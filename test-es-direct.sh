#!/bin/bash

# Direct Elasticsearch Query Test
# Tests the exact query that should be generated

ES_HOST="https://api-dev-wellmed.kalpahealth.com/elastic-dev"
ES_USER="elastic"
ES_PASS="password"

# Get tenant ID from request (you need to replace this with actual tenant ID)
TENANT_ID="4"
INDEX_NAME="local.${TENANT_ID}.visit_registration"

echo "=== Testing Elasticsearch Direct Query ==="
echo "Index: $INDEX_NAME"
echo ""

# Test 1: match_all (should return some results)
echo "Test 1: Match All Query"
curl -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$INDEX_NAME/_search?pretty" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {
      "match_all": {}
    },
    "size": 1
  }'

echo ""
echo "========================================"
echo ""

# Test 2: The hybrid query that SHOULD return no results
echo "Test 2: Hybrid Query with Random String (should return 0)"
curl -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$INDEX_NAME/_search?pretty" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {
      "bool": {
        "must": [
          {
            "bool": {
              "should": [
                {
                  "bool": {
                    "should": [
                      {
                        "match_phrase_prefix": {
                          "patient_name": {
                            "query": "Hamzahshdfsdfsdfsdadgdgadsgf",
                            "boost": 3
                          }
                        }
                      },
                      {
                        "match": {
                          "patient_name": {
                            "query": "Hamzahshdfsdfsdfsdadgdgadsgf",
                            "operator": "and",
                            "boost": 2
                          }
                        }
                      },
                      {
                        "wildcard": {
                          "patient_name.keyword": {
                            "value": "*hamzahshdfsdfsdfsdadgdgadsgf*",
                            "case_insensitive": true
                          }
                        }
                      }
                    ],
                    "minimum_should_match": 1
                  }
                },
                {
                  "bool": {
                    "should": [
                      {
                        "match_phrase_prefix": {
                          "patient_nik": {
                            "query": "Hamzahshdfsdfsdfsdadgdgadsgf",
                            "boost": 3
                          }
                        }
                      },
                      {
                        "match": {
                          "patient_nik": {
                            "query": "Hamzahshdfsdfsdfsdadgdgadsgf",
                            "operator": "and",
                            "boost": 2
                          }
                        }
                      },
                      {
                        "wildcard": {
                          "patient_nik.keyword": {
                            "value": "*hamzahshdfsdfsdfsdadgdgadsgf*",
                            "case_insensitive": true
                          }
                        }
                      }
                    ],
                    "minimum_should_match": 1
                  }
                }
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
  }'

echo ""
echo "========================================"
echo ""

# Test 3: Just the status filter (should return results)
echo "Test 3: Status Filter Only (should return results)"
curl -k -u "$ES_USER:$ES_PASS" -X POST "$ES_HOST/$INDEX_NAME/_search?pretty" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {
      "term": {
        "status": "COMPLETED"
      }
    },
    "size": 1
  }'

echo ""
echo "========================================"
echo ""

# Test 4: Check if the index exists
echo "Test 4: Index Info"
curl -k -u "$ES_USER:$ES_PASS" -X GET "$ES_HOST/$INDEX_NAME?pretty"

echo ""
echo "Done!"
