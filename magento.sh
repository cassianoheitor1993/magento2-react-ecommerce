#!/bin/bash
##############################################################################
# Magento 2 CLI Helper — Run common Magento commands easily
#
# Usage:
#   chmod +x magento.sh
#   ./magento.sh <command>
#
# Examples:
#   ./magento.sh cache:flush
#   ./magento.sh setup:upgrade
#   ./magento.sh module:status
##############################################################################

docker compose exec phpfpm bin/magento "$@"
