<?php

class Philwinkle_LinkGuestOrder_IndexController extends Mage_Core_Controller_Front_Action
{
	protected $_order;
	protected $_cookieName = 'linkguestorder';
	protected $_lifeTime = 600;

    public function preDispatch()
    {
        parent::preDispatch();

        if(!Mage::getSingleton('customer/session')->isLoggedIn()){
        	$this->_redirect('customer/account/login');
        	return;
        }
    }

	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}

	public function viewAction()
	{		
		if(!$this->_loadValidOrder()){
	        Mage::getSingleton('core/session')->addError(
	            $this->__('Entered data is incorrect. Please try again.')
	        );
	        $this->_redirect('*/*/index');
			return;
		}

		$this->loadLayout()->renderLayout();
	}

	public function linkAction()
	{
		$error = false;
		$message = '';
		$params = $this->getRequest()->getParams();

		if(!$this->_loadValidOrder()){
			Mage::getSingleton('core/session')->addError(
	            $this->__('Order not exists. Please try again.')
	        );
			$this->_redirect('*/*/view');
			return;
		}

		$order = Mage::registry('current_order');

		if(!$order->getCustomerIsGuest()){
	        Mage::getSingleton('core/session')->addError(
	            $this->__('An error occurred. Please try again.')
	        );
			$this->_redirect('*/*/view');
			return;
		}
		try {
			$order->setCustomerId(Mage::getSingleton('customer/session')->getCustomer()->getId());
			$order->setCustomerIsGuest(false);
			$order->save();
		} catch(Exception $e){
			Mage::getSingleton('core/session')->addError(
	            $this->__('An error occurred: %s', $e->getMessage())
	        );
			$this->_redirect('*/*/view');
			return;
		}

		Mage::getSingleton('core/session')->addSuccess(
            $this->__('Order %s was successfully linked to the account.',$order->getIncrementId())
        );
		$this->_redirect('*/*/index');
	}

    protected function _loadValidOrder()
    {
        $post = $this->getRequest()->getPost();

        $type           = '';
        $incrementId    = '';
        $lastName       = '';
        $email          = '';
        $zip            = '';
        $protectCode    = '';
        $errors         = false;

        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');

        if (empty($post) && !Mage::getSingleton('core/cookie')->get($this->_cookieName)) {
            $this->_redirect('*/*/index');
            return false;
        } elseif (!empty($post) && isset($post['oar_order_id']) && isset($post['oar_type']))  {
            $type           = $post['oar_type'];
            $incrementId    = $post['oar_order_id'];
            $lastName       = $post['oar_billing_lastname'];
            $email          = $post['oar_email'];
            $zip            = $post['oar_zip'];

            if (empty($incrementId) || empty($lastName) || empty($type) || (!in_array($type, array('email', 'zip')))
                || ($type == 'email' && empty($email)) || ($type == 'zip' && empty($zip))) {
                $errors = true;
            }

            if (!$errors) {
                $order->loadByIncrementId($incrementId);
            }

            if ($order->getId() && $order->getCustomerIsGuest()) {
                $billingAddress = $order->getBillingAddress();
                if ((strtolower($lastName) != strtolower($billingAddress->getLastname()))
                    || ($type == 'email'
                        && strtolower($email) != strtolower($billingAddress->getEmail()))
                    || ($type == 'zip'
                        && (strtolower($zip) != strtolower($billingAddress->getPostcode())))
                ) {
                    $errors = true;
                }
            } else {
                $errors = true;
            }

            if (!$errors) {
                $toCookie = base64_encode($order->getProtectCode());
                Mage::getSingleton('core/cookie')->set($this->_cookieName, $toCookie, $this->_lifeTime, '/');
            }
        } elseif (Mage::getSingleton('core/cookie')->get($this->_cookieName)) {
            $fromCookie     = Mage::getSingleton('core/cookie')->get($this->_cookieName);
            $protectCode    = base64_decode($fromCookie);

            if (!empty($protectCode)) {
                $order->loadByAttribute('protect_code', $protectCode);

                Mage::getSingleton('core/cookie')->renew($this->_cookieName, $this->_lifeTime, '/');
            } else {
                $errors = true;
            }
        }

        if (!$errors && $order->getId()) {
            Mage::register('current_order', $order);
            return true;
        }

        return false;
    }
}