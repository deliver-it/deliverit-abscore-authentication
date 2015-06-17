<?php
namespace ABSCore\Authentication\Identity;

interface IdentityInterface
{
    public function exchangeArray($data);
    public function getArrayCopy();
}
