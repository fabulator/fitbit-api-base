## Fitbit API ##

Very basic Fitbit API. You can work with Oauth tokens and request API endpoints. 

First get login url:

```
$fitbit = new Fabulator\FitbitAPI('YOUR_CLIENT_ID', 'YOUR_SECRET_ID');
$fitbit->getLoginUrl('YOUR_RETURN_URL', ['profile']);
```

You will retrive code after login. Use it to request access token:

```
echo json_decode((string) $fitbit->requestAccessToken('YOUR_CODE', 'YOUR_RETURN_URL')->getBody())->access_token;
```

You can use Access token to request Fitbit API endpoints:

```
print_r(json_decode((string) $fitbit->get('profile')->getBody()))
```