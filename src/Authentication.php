<?php

namespace MBLSolutions\InspiredDeckLaravel;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Session;
use MBLSolutions\InspiredDeck\Authentication as InspiredDeckAuth;
use MBLSolutions\InspiredDeck\Exceptions\AuthenticationException;
use MBLSolutions\InspiredDeckLaravel\Exceptions\InvalidUserRoleException;

class Authentication
{
    /** @var string $key */
    public $sessionKey;

    /** @var InspiredDeckAuth $authResource */
    public $authResource;

    /** @var array $validRoles */
    protected $validRoles;

    /**
     * Inspired Deck Authentication
     *
     * @param InspiredDeckAuth|null $authResource
     */
    public function __construct(InspiredDeckAuth $authResource = null)
    {
        $this->sessionKey = config('inspireddeck.session');
        $this->validRoles = config('inspireddeck.roles');

        $this->authResource = $authResource ?? new InspiredDeckAuth;
    }

    /**
     * Get the currently Authenticated User
     *
     * @return mixed
     */
    public function get()
    {
        return Session::get($this->sessionKey, false);
    }

    /**
     * Authenticate the User using OAuth Password Grant
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws GuzzleException
     * @throws AuthenticationException
     */
    public function login($username, $password): bool
    {
        Session::regenerate();

        $response = $this->authResource->password(
            config('inspireddeck.client_id'),
            config('inspireddeck.secret'),
            $username,
            $password
        );

        return $this->validateUserRole($response) && $this->store($response);
    }

    /**
     * Remove OAuth session
     *
     * @return bool
     */
    public function logout(): bool
    {
        Session::forget($this->sessionKey);

        return true;
    }

    /**
     * Store the OAuth session
     *
     * @param array $auth
     * @return bool
     */
    private function store(array $auth): bool
    {
        Session::put($this->sessionKey, $auth);

        return true;
    }

    /**
     * Validate User Role
     *
     * @param array $response
     * @return bool
     */
    private function validateUserRole(array $response): bool
    {
        $role = $response['user']['role'] ?? null;

        if (!in_array($role, $this->validRoles, true)) {
            throw new InvalidUserRoleException(403, 'Your user role does not have permission for this action');
        }

        return true;
    }

}