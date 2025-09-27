# AS2aaS PHP Client Examples

This directory contains practical examples of how to use the AS2aaS PHP Client.

## Files

- `basic-usage.php` - Comprehensive example showing all major features
- `laravel-integration.php` - Laravel-specific examples with controllers and webhooks

## Running Examples

### Using Test API Keys

The examples are configured to use test API keys, which the AS2aaS API automatically detects and routes to the test environment:

```bash
php examples/basic-usage.php
```

This will demonstrate all features using the test environment.

### Using Production API

To use the production AS2aaS API (same URL, but with live keys):

1. **Get your production API key** from the AS2aaS dashboard
2. **Update the example** by replacing the test key (`pk_test_*`) with your production key (`pk_live_*`)
3. **Run the example**:

```bash
php examples/basic-usage.php
```

### Environment Variable Configuration

You can also set your API key as an environment variable:

```bash
export AS2AAS_API_KEY=pk_test_your_actual_api_key
```

Then modify the example to use:

```php
$as2 = new Client(); // Will automatically use environment variable
```

## Example Output

When running with test API keys, you should see output similar to:

```
=== AS2aaS PHP Client Examples ===

1. Listing partners...
Found 0 partners (for new test accounts)

2. Getting partner by AS2 ID...
Partner 'MCKESSON' not found (this is normal for new accounts): Partner not found

3. Creating test partner...
Created partner: Test Partner 2025-09-26 15:44:28 (ID: prt_test_123)

4. Testing partner connectivity...
Test result: SUCCESS
Message: Partner connectivity verified

5. Sending test message...
Message sent! ID: msg_test_456
Status: delivered

... (additional operations)

=== Examples completed successfully! ===
```

## Key Features Demonstrated

The `basic-usage.php` example demonstrates:

1. **Partner Management**
   - Listing partners
   - Finding partners by AS2 ID
   - Creating new partners
   - Testing partner connectivity

2. **Message Operations**
   - Sending EDI messages
   - Listing recent messages
   - Validating EDI content

3. **Utility Functions**
   - Content type detection
   - AS2 ID generation
   - File size formatting

4. **Certificate Management**
   - Listing certificates

5. **Webhook Management**
   - Listing webhook endpoints

## Laravel Integration

See `laravel-integration.php` for complete Laravel examples including:

- Controllers for order management
- Webhook handling
- Certificate management
- Background job processing
- Error handling patterns

## Error Handling

All examples include proper error handling using the AS2aaS exception classes:

```php
try {
    $message = $as2->messages()->send($partner, $content, $subject);
} catch (AS2PartnerError $e) {
    echo 'Partner issue: ' . $e->getMessage();
} catch (AS2ValidationError $e) {
    echo 'Validation errors: ' . json_encode($e->getValidationErrors());
} catch (AS2Error $e) {
    echo 'General AS2 error: ' . $e->getMessage();
}
```

## Next Steps

1. **Review the examples** to understand the API
2. **Test with test API keys** to familiarize yourself with the interface  
3. **Get production AS2aaS API access** for live operations
4. **Integrate into your application** using the patterns shown

For more detailed documentation, see the main [README.md](../README.md) file.
