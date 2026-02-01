#!/bin/bash

# Dashboard Metrics Elasticsearch Setup Script
# This script sets up the Elasticsearch template for dashboard metrics

set -e

# Configuration
ELASTICSEARCH_HOST="${ELASTICSEARCH_HOST:-localhost:9200}"
ELASTICSEARCH_USER="${ELASTICSEARCH_USER:-elastic}"
ELASTICSEARCH_PASSWORD="${ELASTICSEARCH_PASSWORD:-password}"
TEMPLATE_FILE="elasticsearch/templates/dashboard-metrics-template.json"

echo "==================================="
echo "Dashboard Metrics Elasticsearch Setup"
echo "==================================="
echo ""
echo "Elasticsearch Host: $ELASTICSEARCH_HOST"
echo "Template File: $TEMPLATE_FILE"
echo ""

# Check if Elasticsearch is running
echo "Checking Elasticsearch connection..."
if curl -s -u "$ELASTICSEARCH_USER:$ELASTICSEARCH_PASSWORD" "http://$ELASTICSEARCH_HOST" > /dev/null 2>&1; then
    echo "✓ Elasticsearch is running"
else
    echo "✗ Cannot connect to Elasticsearch at $ELASTICSEARCH_HOST"
    echo "Please ensure Elasticsearch is running and credentials are correct."
    exit 1
fi

# Check if template file exists
if [ ! -f "$TEMPLATE_FILE" ]; then
    echo "✗ Template file not found: $TEMPLATE_FILE"
    exit 1
fi

echo "✓ Template file found"
echo ""

# Install the index template
echo "Installing index template..."
RESPONSE=$(curl -s -w "\n%{http_code}" -X PUT \
    -u "$ELASTICSEARCH_USER:$ELASTICSEARCH_PASSWORD" \
    "http://$ELASTICSEARCH_HOST/_index_template/dashboard-metrics-template" \
    -H 'Content-Type: application/json' \
    -d @"$TEMPLATE_FILE")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✓ Template installed successfully"
    echo ""
    echo "Response: $BODY"
else
    echo "✗ Failed to install template (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
    exit 1
fi

echo ""
echo "==================================="
echo "Verification"
echo "==================================="

# Verify template installation
echo "Verifying template installation..."
TEMPLATE_CHECK=$(curl -s -u "$ELASTICSEARCH_USER:$ELASTICSEARCH_PASSWORD" \
    "http://$ELASTICSEARCH_HOST/_index_template/dashboard-metrics-template")

if echo "$TEMPLATE_CHECK" | grep -q "dashboard-metrics-template"; then
    echo "✓ Template verification successful"
else
    echo "✗ Template verification failed"
    exit 1
fi

echo ""
echo "==================================="
echo "Setup Complete!"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Update your .env.backbone file with Elasticsearch configuration"
echo "2. Set ELASTICSEARCH_ENABLED=true"
echo "3. Use DashboardMetricsService to store and retrieve metrics"
echo ""
echo "Example usage:"
echo "  php artisan tinker"
echo "  >>> \$service = app(\Projects\WellmedBackbone\Services\DashboardMetricsService::class);"
echo "  >>> \$data = include('elasticsearch/examples/sample-dashboard-data.php');"
echo "  >>> \$service->store(\$data['daily'], 'daily');"
echo ""
echo "View documentation: DASHBOARD_METRICS_ELASTICSEARCH.md"
echo ""
