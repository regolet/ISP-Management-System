# API Documentation

## Overview

This API provides access to ISP management system functionality, including plans and deductions management. All endpoints require authentication via an API key.

## Authentication

Include your API key in the request headers:
```
X-API-KEY: your_api_key_here
```

## Rate Limiting

Each API key has a rate limit defined in requests per minute. The default is 60 requests per minute. If you exceed this limit, you'll receive a 429 (Too Many Requests) response.

## Response Format

All API responses follow this format:
```json
{
    "success": true|false,
    "data": {...} | null,
    "error": "Error message" | null
}
```

## Plans API

### List Active Plans
```
GET /api/plans
```
Returns a list of all active internet service plans.

### Search Plans
```
GET /api/plans/search
```
Search plans with filters.

Query Parameters:
- q: Search query string
- bandwidth_min: Minimum bandwidth in Mbps
- bandwidth_max: Maximum bandwidth in Mbps
- price_min: Minimum price
- price_max: Maximum price
- status: Plan status (active|inactive)

### Compare Plans
```
GET /api/plans/compare
```
Compare multiple plans side by side.

Query Parameters:
- plans: Array of plan IDs to compare

### Get Plan Details
```
GET /api/plans/{id}
```
Get detailed information about a specific plan.

### Get Plan Subscribers
```
GET /api/plans/{id}/subscribers
```
Get a list of subscribers for a specific plan.

## Deductions API

### List Deduction Types
```
GET /api/deductions/types
```
Get a list of all deduction types.

Query Parameters:
- active: Filter by active status (true|false)

### Get Employee Deductions
```
GET /api/deductions/employee/{id}
```
Get all deductions for a specific employee.

Query Parameters:
- status: Filter by status (active|completed|cancelled)

### Get Deduction History
```
GET /api/deductions/{id}/history
```
Get the complete history of a specific deduction.

### Calculate Deduction
```
POST /api/deductions/calculate
```
Calculate deduction amount based on type and base amount.

Request Body:
```json
{
    "type_id": 123,
    "base_amount": 1000.00
}
```

### Get Payroll Summary
```
GET /api/deductions/payroll-summary/{employeeId}/{startDate}/{endDate}
```
Get a summary of deductions for payroll processing.

## Error Codes

- 400: Bad Request - Invalid parameters or request body
- 401: Unauthorized - Invalid or missing API key
- 403: Forbidden - Insufficient permissions
- 404: Not Found - Resource not found
- 429: Too Many Requests - Rate limit exceeded
- 500: Internal Server Error - Server-side error

## Examples

### List Active Plans
```bash
curl -X GET \
  'http://your-domain.com/api/plans' \
  -H 'X-API-KEY: your_api_key_here'
```

### Calculate Deduction
```bash
curl -X POST \
  'http://your-domain.com/api/deductions/calculate' \
  -H 'X-API-KEY: your_api_key_here' \
  -H 'Content-Type: application/json' \
  -d '{
    "type_id": 123,
    "base_amount": 1000.00
  }'
```

## Best Practices

1. Always include error handling in your API client code
2. Cache responses when appropriate to avoid hitting rate limits
3. Use appropriate HTTP methods for each operation
4. Include relevant query parameters to filter results
5. Handle pagination for large result sets

## Support

For API support or to report issues:
- Email: api-support@example.com
- Documentation: https://docs.example.com/api
- Status Page: https://status.example.com
