# oauth2-clickpink


```json
{

    "require": {
        "azuracom/oauth2-clickpink": "^1.0",
        "knpuniversity/oauth2-client-bundle": "^2.18",
    },
    "repositories":[

        {
            "type": "vcs",
            "url": "git@github.com:azuracom/oauth2-clickpink.git"
        }
    ],
}
```

```sh
composer update azuracom/oauth2-clickpink
composer update knpuniversity/oauth2-client-bundle
```


```yaml
# config/packages/knpu_oauth2_client.yaml
knpu_oauth2_client:
    clients:
        # configure your clients as described here: https://github.com/knpuniversity/oauth2-client-bundle#configuration
        clickpink:
            type: generic
            provider_class: Azuracom\OAuth2\Client\Provider\ClickPink
            client_id: '%env(OAUTH_CLICKPINK_ID)%'
            client_secret: '%env(OAUTH_CLICKPINK_SECRET)%'
            redirect_route: connect_clickpink_check
            redirect_params: {}
            provider_options:
                environment: '%kernel.environment%' # Important to set the base url
```

```yaml
# config/packages/security.yaml
security:
   enable_authenticator_manager: true
   firewalls:
        main:
            #...
            entry_point: form_login
            custom_authenticators:
                - App\Security\Authenticator\ClickPinkAuthenticator
```


```php
// src/Controller/ClickPinkController.php
<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ClickPinkController extends AbstractController
{

    #[Route('/connect/click-pink', name: 'connect_clickpink_start')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('clickpink') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([]);
    }


    #[Route('/connect/click-pink/check', name: 'connect_clickpink_check')]
    public function connectCheckAction()
    {
        throw new \Exception('Please define an authenticator for ClickPink.');
    }
}
```

```php
// src/Security/Authenticator/ClickPinkAuthenticator.php
<?php

namespace App\Security\Authenticator;

use App\Entity\User;
use Azuracom\OAuth2\Client\Provider\ClickPinkUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ClickPinkAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private RequestStack $requestStack,
    ) {}

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_clickpink_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('clickpink');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var ClickPinkUser $clickpinkUser */
                $clickpinkUser = $client->fetchUserFromToken($accessToken);
                $email = $clickpinkUser->getEmail();

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if (!$user) {
                    $user = new User();
                    $user->setRoles(['ROLE_USER']);
                    $user->setEmail($email);
                    $this->entityManager->persist($user);
                    $user->setPassword(''); // no password needed
                }

                //Update user with new data
                $user->setFirstname($clickpinkUser->getFirstname());
                $user->setLastname($clickpinkUser->getLastname());
                $user->setLocale($clickpinkUser->getLocale());

                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('app_homepage');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $this->requestStack->getSession()->getFlashBag()->add('danger', $message);
        $url = $this->router->generate('app_login');

        return new RedirectResponse($url);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
```


```twig
    <a href="{{ path('connect_clickpink_start') }}" class="btn">
        Connect
    </a>
```
