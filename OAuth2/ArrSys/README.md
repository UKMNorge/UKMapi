# API Call with authorization on Arrangørsystemet

<hr>

# HandleAPICallWithAuthorization

The `HandleAPICallWithAuthorization` class extends the `HandleAPICall` class and adds authorization control for API calls. This class ensures that the user has the appropriate access level to proceed with the API request.

## Features

- **Authorization Control**: Automatically checks user permissions based on access types such as `fylke`, `kommune`, `arrangement`, or specific roles like `superadmin`.
- **Access Control**: Verifies if the current user has access to the requested resource and sends an error response if access is denied.
- **Supports Multiple Access Levels**: The class supports access types for fylke, kommune, events arrangement, and more.
- **Handles Errors Automatically**: If access is denied, the error message is sent to the client, and the execution is stopped. This happens on initialization of HandleAPICallWithAuthorization 
- **Pre-initialization Request Argument Handling**: Retrieve specific request arguments before the initialization of the application context.

## Installation

1. Clone or download the repository.
2. Include the required namespaces and ensure that dependencies like `UKMNorge\OAuth2\HandleAPICall`, `UKMNorge\OAuth2\ArrSys\AccessControlArrSys`, and others are properly set up.

## Usage

### Constructor

To use the `HandleAPICallWithAuthorization` class, instantiate it by passing in the required arguments. If the user does not have the required access level, the constructor will automatically send an error to the client and stop execution.

### Access levels

| Name      | Argument   | Description                |
|-----------|--------|----------------------------|
| `null` | `null` | Returns always true and no check is performed |
| `superadmin` | `null` | Superadmin brukere på Wordpress |
| `fylke` | `null or fylkeId` | If the argument is null, the user must be admin at minimum 1 fylke. When argument is provided, the user must be admin at fylke with fylkeId |
| `kommune` | `null or kommuneId` | If the argument is null, the user must be admin at minimum 1 kommune. When argument is provided, the user must be admin at kommune with kommuneId |
| `arrangement` | `null or arrangementId` | If the argument is null, the user must be admin at minimum 1 arrangement. When argument is provided, the user must be admin at arrangement with arrangementId |
| `fylke_fra_kommune` | `fylkeId` | Checks if the user has access to minimum 1 kommune in this fylke (fylkeId). It is also true if the user has direct access to the fylke
| `arrangement_i_kommune_fylke` | `arrangementId` | Checks if the user has access to kommune the arrangement is registered in or fylke the kommune belongs to
| `kommune_eller_fylke ` | `kommuneId` | Checks if the user as access to kommune or has access to the fylke that kommune is part of 
IMPORTANT: The name of access level above is `accessType` argument of the constructor and argument above is `accessValue` argument on the constructor.

#### Parameters:

- `$requiredArguments` (array): An array of required arguments for the API call.
- `$optionalArguments` (array): An array of optional arguments for the API call.
- `$acceptedMethods` (array): Accepted HTTP methods (e.g., `GET`, `POST`).
- `$loginRequired` (bool): Indicates whether login is required to access the API.
- `$wordpressLogin` (bool): Indicates whether WordPress login is required.
- `$accessType` (string|null): The type of access required (e.g., `fylke` for regional, `kommune` for local).
- `$accessValue` (string|null): Additional values required for access verification (e.g., fylke ID).

### Example Usage:

```php
$apiCallHandler = new HandleAPICallWithAuthorization(
    ['requiredArg1', 'requiredArg2'], 
    ['optionalArg1'], 
    ['GET', 'POST'], 
    true,  // login required
    false, // WordPress login not required
    'kommune', 
    '123'  // Access to a specific municipality
);
```
