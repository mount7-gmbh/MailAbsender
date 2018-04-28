<?php

/*    Please retain this copyright header in all versions of the software
 *
 *    Copyright (C) Josef A. Puckl | eComStyle.de
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see {http://www.gnu.org/licenses/}.
 */
 
namespace Ecs\MailAbsender\Core;

class Email extends Email_parent
{

    public function sendOrderEmailToOwner($order, $subject = null)
    {
        $config = $this->getConfig();
        $shop   = $this->_getShop();
        // cleanup
        $this->_clearMailer();
        // add user defined stuff if there is any
        $order = $this->_addUserInfoOrderEMail($order);
        $user  = $order->getOrderUser();
        $this->setUser($user);
        // send confirmation to shop owner
        // send not pretending from order user, as different email domain rise spam filters
        //ORGINAL:$this->setFrom($shop->oxshops__oxowneremail->value);
        //START:
        if ($user->oxuser__oxcompany->value) {
            $sFullName = $user->oxuser__oxcompany->getRawValue() . ' | ';
        }

        $sFullName .= $user->oxuser__oxfname->getRawValue() . " " . $user->oxuser__oxlname->getRawValue();
        $this->setFrom($user->oxuser__oxusername->value, $sFullName);
        //END
        $language      = \OxidEsales\Eshop\Core\Registry::getLang();
        $orderLanguage = $language->getObjectTplLanguage();
        // if running shop language is different from admin lang. set in config
        // we have to load shop in config language
        if ($shop->getLanguage() != $orderLanguage) {
            $shop = $this->_getShop($orderLanguage);
        }
        $this->setSmtp($shop);
        // create messages
        $smarty = $this->_getSmarty();
        $this->setViewData("order", $order);
        // Process view data array through oxoutput processor
        $this->_processViewArray();
        $this->setBody($smarty->fetch($config->getTemplatePath($this->_sOrderOwnerTemplate, false)));
        $this->setAltBody($smarty->fetch($config->getTemplatePath($this->_sOrderOwnerPlainTemplate, false)));
        //Sets subject to email
        // #586A
        if ($subject === null) {
            if ($smarty->template_exists($this->_sOrderOwnerSubjectTemplate)) {
                $subject = $smarty->fetch($this->_sOrderOwnerSubjectTemplate);
            } else {
                $subject = $shop->oxshops__oxordersubject->getRawValue() . " (#" . $order->oxorder__oxordernr->value . ")";
            }
        }
        $this->setSubject($subject);
        $this->setRecipient($shop->oxshops__oxowneremail->value, $language->translateString("order"));
        if ($user->oxuser__oxusername->value != "admin") {
            $fullName = $user->oxuser__oxfname->getRawValue() . " " . $user->oxuser__oxlname->getRawValue();
            $this->setReplyTo($user->oxuser__oxusername->value, $fullName);
        }
        $result = $this->send();
        $this->onOrderEmailToOwnerSent($user, $order);
        if ($config->getConfigParam('iDebug') == 6) {
            \OxidEsales\Eshop\Core\Registry::getUtils()->showMessageAndExit("");
        }
        return $result;
    }

    public function sendContactMail($emailAddress = null, $subject = null, $message = null)
    {
        // shop info
        $shop = $this->_getShop();
        //set mail params (from, fromName, smtp)
        $this->_setMailParams($shop);
        $this->setBody($message);
        $this->setSubject($subject);
        $this->setRecipient($shop->oxshops__oxinfoemail->value, "");
        // Original: $this->setFrom($shop->oxshops__oxowneremail->value, $shop->oxshops__oxname->getRawValue());
        //START:
        $this->setFrom($emailAddress, "");
        //END
        $this->setReplyTo($emailAddress, "");
        return $this->send();
    }
}
