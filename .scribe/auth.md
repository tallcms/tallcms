# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_API_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Get a token by POSTing to <code>/api/v1/tallcms/auth/token</code> with your email, password, device_name, and abilities. Use the returned token as: <code>Authorization: Bearer {token}</code>
