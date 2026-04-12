#!/bin/bash
# Quick deployment script for ARM server

set -e

echo "🚀 Wellmed ARM Deployment Script"
echo "=================================="

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if docker-compose file exists
if [ ! -f "docker-compose-dev-arm.yaml" ]; then
    print_error "docker-compose-dev-arm.yaml not found!"
    exit 1
fi

# Parse command line arguments
COMMAND=${1:-up}

case $COMMAND in
    up)
        print_info "Building and starting ARM containers..."
        docker compose -f docker-compose-dev-arm.yaml up -d --build
        print_success "Containers started successfully"

        echo ""
        print_info "Waiting for services to be healthy..."
        sleep 5

        echo ""
        print_info "Container Status:"
        docker compose -f docker-compose-dev-arm.yaml ps

        echo ""
        print_success "Deployment completed!"
        echo ""
        print_info "Access your application at: http://localhost:9000"
        print_info "PgBouncer stats: docker exec -it wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer -c 'SHOW POOLS;'"
        ;;

    down)
        print_info "Stopping ARM containers..."
        docker compose -f docker-compose-dev-arm.yaml down
        print_success "Containers stopped successfully"
        ;;

    restart)
        print_info "Restarting ARM containers..."
        docker compose -f docker-compose-dev-arm.yaml restart
        print_success "Containers restarted successfully"
        ;;

    logs)
        SERVICE=${2:-wellmed}
        print_info "Showing logs for: $SERVICE"
        docker compose -f docker-compose-dev-arm.yaml logs -f $SERVICE
        ;;

    build)
        SERVICE=${2:-}
        if [ -z "$SERVICE" ]; then
            print_info "Building all ARM containers..."
            docker compose -f docker-compose-dev-arm.yaml build
        else
            print_info "Building $SERVICE..."
            docker compose -f docker-compose-dev-arm.yaml build $SERVICE
        fi
        print_success "Build completed successfully"
        ;;

    rebuild)
        SERVICE=${2:-}
        if [ -z "$SERVICE" ]; then
            print_info "Rebuilding all ARM containers..."
            docker compose -f docker-compose-dev-arm.yaml down
            docker compose -f docker-compose-dev-arm.yaml build --no-cache
            docker compose -f docker-compose-dev-arm.yaml up -d
        else
            print_info "Rebuilding $SERVICE..."
            docker compose -f docker-compose-dev-arm.yaml stop $SERVICE
            docker compose -f docker-compose-dev-arm.yaml build --no-cache $SERVICE
            docker compose -f docker-compose-dev-arm.yaml up -d $SERVICE
        fi
        print_success "Rebuild completed successfully"
        ;;

    status)
        print_info "Container Status:"
        docker compose -f docker-compose-dev-arm.yaml ps

        echo ""
        print_info "PgBouncer Pool Status:"
        docker exec wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer -c "SHOW POOLS;" 2>/dev/null || print_error "PgBouncer not running"
        ;;

    db-test)
        print_info "Testing database connection..."
        docker exec wellmed-backbone php artisan tinker --execute="echo 'Testing DB connection...'; \$result = DB::select('SELECT 1 as test'); var_dump(\$result); echo 'Connection successful!';"
        ;;

    pgbouncer)
        print_info "Connecting to PgBouncer admin console..."
        docker exec -it wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer
        ;;

    shell)
        SERVICE=${2:-wellmed-backbone}
        print_info "Opening shell in $SERVICE..."
        docker exec -it $SERVICE /bin/bash
        ;;

    *)
        echo "Usage: $0 {up|down|restart|logs|build|rebuild|status|db-test|pgbouncer|shell} [service]"
        echo ""
        echo "Commands:"
        echo "  up         - Build and start all containers"
        echo "  down       - Stop all containers"
        echo "  restart    - Restart all containers"
        echo "  logs       - Show logs (usage: $0 logs [service])"
        echo "  build      - Build containers (usage: $0 build [service])"
        echo "  rebuild    - Rebuild from scratch (usage: $0 rebuild [service])"
        echo "  status     - Show container and PgBouncer status"
        echo "  db-test    - Test database connection"
        echo "  pgbouncer  - Connect to PgBouncer admin console"
        echo "  shell      - Open shell in container (usage: $0 shell [container])"
        echo ""
        echo "Examples:"
        echo "  $0 up"
        echo "  $0 logs wellmed"
        echo "  $0 build wellmed_pgbouncer"
        echo "  $0 rebuild wellmed"
        echo "  $0 shell wellmed-pgbouncer-arm"
        exit 1
        ;;
esac
