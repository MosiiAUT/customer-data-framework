<?php

namespace CustomerManagementFrameworkBundle\Model\OAuth;

/**
 * OAuth2 token response model. The response returns a expires_in, but this expects an absolute expires_at.
 *
 * @see https://tools.ietf.org/html/rfc6749#section-5.1
 */
interface OAuth2TokenInterface extends OAuthTokenInterface
{
    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Set access token
     *
     * @param string $accessToken
     *
     * @return $this
     */
    public function setAccessToken($accessToken);

    /**
     * Get refresh token
     *
     * @return string
     */
    public function getRefreshToken();

    /**
     * Set refresh token
     *
     * @param string $refreshToken
     *
     * @return $this
     */
    public function setRefreshToken($refreshToken);

    /**
     * Get token type
     *
     * @return string
     */
    public function getTokenType();

    /**
     * Set token type
     *
     * @param string $tokenType
     *
     * @return $this
     */
    public function setTokenType($tokenType);

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope();

    /**
     * Set scope
     *
     * @param string $scope
     *
     * @return $this
     */
    public function setScope($scope);

    /**
     * Get expires at
     *
     * @return int
     */
    public function getExpiresAt();

    /**
     * Set expires at
     *
     * @param int $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt($expiresAt);
}
