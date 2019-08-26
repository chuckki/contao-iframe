<?php


namespace Chuckki\HvzIframeBundle\Services;


use Contao\CoreBundle\Exception\AccessDeniedException;

class Authenticator
{

    private $iframe_user;

    /**
     * Authenticator constructor.
     */
    public function __construct($iframe_user)
    {
        $this->iframe_user = $iframe_user;
    }

    public function getApiPwForUser($customer)
    {
        return $this->iframe_user[$customer];
    }

    public function isUserAuth($customer)
    {
        if(!array_key_exists($customer, $this->iframe_user))
        {
            throw new AccessDeniedException('Access Denied');
        }
    }

}
