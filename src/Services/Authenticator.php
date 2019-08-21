<?php


namespace Chuckki\HvzIframeBundle\Services;


class Authenticator
{

    private $frameOptions;

    /**
     * Authenticator constructor.
     */
    public function __construct($frameOptions)
    {
        $this->frameOptions = $frameOptions;
    }

    public function getUsers()
    {
        dump($this->frameOptions);
    }

    public function getApiPwForUser($customer)
    {

    }

    public function isUserAuth($customer)
    {
        return true;
    }

}