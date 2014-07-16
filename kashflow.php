<?php

class KashflowAPI
{
   public $webservice_url = 'https://securedwebapp.com/api/service.asmx';
   public $username;
   private $password;

   private $curl;
   private $headers;

   public $request;
   public $response;

   public $error_msg = array();

   function __construct($user, $pass)
   {
      // Set the username and password
      $this->username = $user;
      $this->password = $pass;

      // Hide some simple XML errors we dont want to see
      libxml_use_internal_errors(true);
   }

   private function dataToXml($data)
   {
      $xml = "";

      foreach($data as $key=>$value) {
         $xml .= "<" . $key . ">" . (is_array($value) ? $this->dataToXml($value) : $value) . "</" . $key . ">";
      }

      return $xml;
   }

   private function SendRequest($xml, $task)
   {
      $result = false;

      // Build the SOAP request
      $this->request = '<?xml version="1.0" encoding="utf-8"?>';
      $this->request .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
      $this->request .= '<soap:Body>';
      $this->request .= '<' . $task . ' xmlns="KashFlow">';
      $this->request .= '<UserName>' . $this->username . '</UserName>';
      $this->request .= '<Password>' . $this->password . '</Password>';
      $this->request .= $xml;
      $this->request .= '</' . $task . '></soap:Body></soap:Envelope>';

      // Build the HTTP headers
      $this->headers = array();
      $this->headers[] = 'User-Agent: KashFlowPhpKit';
      $this->headers[] = 'Host: secure.kashflow.co.uk';
      $this->headers[] = 'Content-Type: text/xml; charset=utf-8';
      $this->headers[] = 'Accept: text/xml';
      $this->headers[] = 'Content-Length: ' . strlen($this->request);
      $this->headers[] = 'SOAPAction: "KashFlow/' . $task . '"';

      // Send the request over to KashFlow
      $this->curl = curl_init();
      curl_setopt($this->curl, CURLOPT_URL, $this->webservice_url);
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($this->curl, CURLOPT_POST, 1);
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->request);
      $output = curl_exec($this->curl);

      // Check for cURL errors
      if (curl_errno($this->curl)) {
         // Define error details
         $curl_error = curl_error($this->curl);
         $curl_errorno = curl_errno($this->curl);

         // Close the connection
         curl_close($this->curl);

         // Return the error message
         $this->error_msg['ErrorMsg'] = 'cURL encountered an error connecting to the KashFlow web service, this was reported as ' . $curl_error;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         // Close the connection
         curl_close($this->curl);

         // Return the API output
         $output = preg_replace('|<([/\w]+)(:)|m', '<$1', preg_replace('|(\w+)(:)(\w+=\")|m', '$1$3', $output));
         $this->response = $output;

         return simplexml_load_string($output);
      }
   }

   private function object_to_array($xml)
   {
      if (is_object($xml) && get_class($xml) == 'SimpleXMLElement') {
         $attributes = $xml->attributes();
         foreach ($attributes as $k => $v) {
            if ($v) {
               $a[$k] = (string)$v;
            }
         }
         $x = $xml;
         $xml = get_object_vars($xml);
      }

      if (is_array($xml)) {
         if (count($xml) == 0) {
            return (string)$x;
         } // for CDATA
         foreach ($xml as $key => $value) {
            $r[$key] = $this->object_to_array($value);
         }

         if (isset($a)) {
            $r['@'] = $a;
         } // Attributes

         return $r;
      }

      return (string)$xml;
   }

   private function clean_dataset($data)
   {
      $new_data = array();

      if (isset($data['0']) == false) {
         $new_data['0'] = $data;
      } else {
         $new_data = $data;
      }

      return $new_data;
   }

   ///////////////////////////////////////////////////
   // CUSTOMERS
   ///////////////////////////////////////////////////

   public function GetCustomer($customer_code)
   {
      $xml = '<CustomerCode>' . (string)$customer_code . '</CustomerCode>';

      $response = $this->SendRequest($xml, 'GetCustomer');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetCustomerResponse->GetCustomerResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomerByID($customer_id)
   {
      $xml = '<CustomerID>' . (int)$customer_id . '</CustomerID>';

      $response = $this->SendRequest($xml, 'GetCustomerByID');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerByIDResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetCustomerByIDResponse->GetCustomerByIDResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerByIDResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomerByEmail($email)
   {
      $xml = '<CustomerEmail>' . (string)$email . '</CustomerEmail>';

      $response = $this->SendRequest($xml, 'GetCustomerByEmail');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerByEmailResponse->Status == 'OK') {
            return $this->object_to_array(
               $response->soapBody->GetCustomerByEmailResponse->GetCustomerByEmailResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerByEmailResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomers()
   {
      $response = $this->SendRequest('', 'GetCustomers');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomersResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetCustomersResponse->GetCustomersResult;

            return $this->clean_dataset($this->object_to_array($array['Customer']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomersResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomersModifiedSince($data)
   {
      // Returns all data held in an array Customer

      $xml = '<ModifiedSince>' . (string)$data['ModifiedSince'] . '</ModifiedSince>';

      $response = $this->SendRequest($xml, 'GetCustomersModifiedSince');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomersModifiedSinceResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetCustomersModifiedSinceResponse->GetCustomersModifiedSinceResult;

            return $this->clean_dataset($this->object_to_array($array['Customer']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomersModifiedSinceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function InsertCustomer($data)
   {
      $xml = '<custr>' . $this->dataToXml($data) . '</custr>';

      $response = $this->SendRequest($xml, 'InsertCustomer');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertCustomerResponse->Status == 'OK') {
            return (int)$response->soapBody->InsertCustomerResponse->InsertCustomerResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function UpdateCustomer($data) {

      $xml = '<custr>' . $this->dataToXml($data) . '</custr>';

      $response = $this->SendRequest($xml, 'UpdateCustomer');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->UpdateCustomerResponse->Status == 'OK') {
            return (string)$response->soapBody->UpdateCustomerResponse->UpdateCustomerResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->UpdateCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function DeleteCustomer($customer_id)
   {
      // Returns 0 regardless of customer deleted or not

      $xml = '<CustomerID>' . $customer_id . '</CustomerID>';

      $response = $this->SendRequest($xml, 'DeleteCustomer');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->DeleteCustomerResponse->Status == 'OK') {
            if ($response->soapBody->DeleteCustomerResponse->DeleteCustomerResult == '1') {
               return true;
            } else {
               return false;
            }
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomerSources()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetCustomerSources');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerSourcesResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetCustomerSourcesResponse->GetCustomerSourcesResult;

            return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerSourcesResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomerVATNumber($data)
   {
      $xml = '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

      $response = $this->SendRequest($xml, 'GetCustomerVATNumber');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerVATNumberResponse->Status == 'OK') {
            return $this->clean_dataset(
               (array)$response->soapBody->GetCustomerVATNumberResponse->GetCustomerVATNumberResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerVATNumberResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function SetCustomerVATNumber($data)
   {
      $xml = '<CustVATNumber>' . $data['CustVATNumber'] . '</CustVATNumber>';
      $xml .= '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

      $response = $this->SendRequest($xml, 'SetCustomerVATNumber');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->SetCustomerVATNumberResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->SetCustomerVATNumberResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetCustomerCurrency($data)
   {
      $xml = '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

      $response = $this->SendRequest($xml, 'GetCustomerCurrency');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetCustomerCurrencyResponse->Status == 'OK') {
            return $this->clean_dataset(
               (array)$response->soapBody->GetCustomerCurrencyResponse->GetCustomerCurrencyResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerCurrencyResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function SetCustomerCurrency($data)
   {
      $xml = '<CurrencyCode>' . $data['CurrencyCode'] . '</CurrencyCode>';
      $xml .= '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

      $response = $this->SendRequest($xml, 'SetCustomerCurrency');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->SetCustomerCurrencyResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->SetCustomerCurrencyResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // QUOTES
   ///////////////////////////////////////////////////


   public function GetQuotes()
   {
      $response = $this->SendRequest('', 'GetQuotes');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         $array = (array)$response->soapBody->GetQuotesResponse->GetQuotesResult;
         if (is_array($array) && !empty($array)) {
            return $this->clean_dataset($this->object_to_array($array['Invoice']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetQuotesResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function DeleteQuote($data)
   {
      // Returns 0 regardless of quote deleted or not

      $xml = '<QuoteNumber>' . $data['InvoiceNumber'] . '</QuoteNumber>';

      $response = $this->SendRequest($xml, 'DeleteQuote');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->DeleteQuoteResponse->Status == 'OK') {
            if ($response->soapBody->DeleteQuoteResponse->DeleteQuoteResult == '1') {
               return true;
            } else {
               return false;
            }
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteQuoteResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // INVOICES
   ///////////////////////////////////////////////////

   public function InsertInvoice($data)
   {
      $xml = '<Inv>' . $this->dataToXml($data) . '</Inv>';

      $response = $this->SendRequest($xml, 'InsertInvoice');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertInvoiceResponse->Status == 'OK') {
            return $this->clean_dataset(
               $this->object_to_array(
                  $response->soapBody->InsertInvoiceResponse->InsertInvoiceResult
               )
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertInvoiceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function UpdateInvoice($data)
   {
      $xml = '<Inv>' . $this->dataToXml($data) . '</Inv>';

      $response = $this->SendRequest($xml, 'UpdateInvoice');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->UpdateInvoiceResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->UpdateInvoiceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }


   public function DeleteInvoice($invoice_id)
   {
      $xml = '<InvoiceNumber>' . $invoice_id . '</InvoiceNumber>';

      $response = $this->SendRequest($xml, 'DeleteInvoice');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->DeleteInvoiceResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteInvoiceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // INVOICE LINES
   ///////////////////////////////////////////////////

   public function GetInvoice($invoice_id)
   {
      $xml = '<InvoiceNumber>' . (int) $invoice_id . '</InvoiceNumber>';
      $response = $this->SendRequest($xml, 'GetInvoice');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetInvoiceResponse->Status == 'OK') {
            return (array)$response->soapBody->GetInvoiceResponse->GetInvoiceResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetInvoiceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function insertInvoiceLineWithInvoiceNumber($invoice_no, $data) {
      $xml =   '<InvoiceNumber>' . $invoice_no . '</InvoiceNumber>';
      $xml .= '<InvLine>' . $this->dataToXml($data) . '</InvLine>';

      $response = $this->SendRequest($xml, 'InsertInvoiceLineWithInvoiceNumber');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertInvoiceLineWithInvoiceNumberResponse->Status == 'OK') {
            return $this->clean_dataset(
               $this->object_to_array(
                  $response->soapBody->InsertInvoiceLineWithInvoiceNumberResponse->InsertInvoiceLineWithInvoiceNumberResult
               )
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertInvoiceLineWithInvoiceNumberResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function deleteInvoiceLine($invoice_id, $line_no)
   {
      $xml = '<InvoiceNumber>' . $invoice_id . '</InvoiceNumber>';
      $xml .= '<LineID>' . $line_no . '</LineID>';

      $response = $this->SendRequest($xml, 'DeleteInvoiceLine');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->DeleteInvoiceLineResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteInvoiceLineResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // INVOICE PAYMENTS
   ///////////////////////////////////////////////////

   public function GetInvoicePayment($data)
   {
      $xml = '<InvoiceNumber>' . (string)$data['InvoiceNumber'] . '</InvoiceNumber>';

      $response = $this->SendRequest($xml, 'GetInvoicePayment');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetInvoicePaymentResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetInvoicePaymentResponse->GetInvoicePaymentResult;

            if (empty($array)) {
               return array();
            } else {
               return $this->clean_dataset($this->object_to_array($array['Payment']));
            }
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetInvoicePaymentResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetInvPayMethods()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetInvPayMethods');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetInvPayMethodsResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetInvPayMethodsResponse->GetInvPayMethodsResult;

            return $this->clean_dataset($this->object_to_array($array['PaymentMethod']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetInvPayMethodsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function InsertInvoicePayment($data)
   {
      $xml = '<InvoicePayment>' . $this->dataToXml($data) . '</InvoicePayment>';

      $response = $this->SendRequest($xml, 'InsertInvoicePayment');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertInvoicePaymentResponse->Status == 'OK') {
            return $this->clean_dataset(
               $this->object_to_array(
                  $response->soapBody->InsertInvoicePaymentResponse->InsertInvoicePaymentResult
               )
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertInvoicePaymentResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function DeleteInvoicePayment($data)
   {
      $xml = '<InvoicePaymentNumber>' . (int)$data['PayID'] . '</InvoicePaymentNumber>';

      $response = $this->SendRequest($xml, 'DeleteInvoicePayment');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->DeleteInvoicePaymentResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteInvoicePaymentResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function getInvoicesByDateRange($data)
   {
      $xml = '<StartDate>' . $data['StartDate'] . '</StartDate>';
      $xml .= '<EndDate>' . $data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetInvoicesByDateRange');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if (is_object($response->soapBody->GetInvoicesByDateRangeResponse)) {
            return $this->object_to_array(
               $response->soapBody->GetInvoicesByDateRangeResponse->GetInvoicesByDateRangeResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function applyCreditNoteToInvoice($invoice_id, $credit_invoice_id)
   {
      $xml = '<InvoiceID>' . $invoice_id . '</InvoiceID>';
      $xml .= '<CreditNoteID>' . $credit_invoice_id . '</CreditNoteID>';
      
      $response = $this->SendRequest($xml, 'applyCreditNoteToInvoice');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->applyCreditNoteToInvoiceResponse->Status == 'OK') {
            return true;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->applyCreditNoteToInvoiceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // SUPPLIERS
   ///////////////////////////////////////////////////

   public function InsertSupplier($data)
   {
      $xml = '<supl>' . $this->dataToXml($data) . '</supl>';

      $response = $this->SendRequest($xml, 'InsertSupplier');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertSupplierResponse->Status == 'OK') {
            return (int)$response->soapBody->InsertSupplierResponse->InsertSupplierResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertSupplierResult->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetSupplier($supplier_id)
   {
      $xml = '<SupplierID>' . $supplier_id . '</SupplierID>';

      $response = $this->SendRequest($xml, 'GetSupplierByID');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if (is_object($response->soapBody->GetSupplierByIDResponse)) {
            return $this->object_to_array(
               $response->soapBody->GetSupplierByIDResponse->GetSupplierByIDResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetSupplierByIDResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetSuppliers()
   {
      $response = $this->SendRequest('', 'GetSuppliers');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetSuppliersResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetSuppliersResponse->GetSuppliersResult;
            return $this->clean_dataset($this->object_to_array($array['Supplier']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetSuppliersResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // PURCHASES
   ///////////////////////////////////////////////////

   public function GetReceipt($receipt_id)
   {

      $xml = '<ReceiptNumber>' . $receipt_id . '</ReceiptNumber>';

      $response = $this->SendRequest($xml, 'GetReceipt');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if (is_object($response->soapBody->GetReceiptResponse)) {
            return $this->object_to_array(
               $response->soapBody->GetReceiptResponse->GetReceiptResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetReceiptResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function InsertReceipt($data)
   {

      $xml = '<Inv>' . $this->dataToXml($data) . '</Inv>';

      $response = $this->SendRequest($xml, 'InsertReceipt');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertReceiptResponse->Status == 'OK') {
            return (int)$response->soapBody->InsertReceiptResponse->InsertReceiptResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertReceiptResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function InsertReceiptLine($receipt_id, $data)
   {
      $xml = "<ReceiptID>" . $receipt_id . "</ReceiptID>";
      $xml .= '<InvLine>' . $this->dataToXml($data) . '</InvLine>';

      $response = $this->SendRequest($xml, 'InsertReceiptLine');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertReceiptLineResponse->Status == 'OK') {
            return (int)$response->soapBody->InsertReceiptLineResponse->InsertReceiptLineResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertReceiptLineResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function InsertReceiptNote($receipt_id, $note, $date = null)
   {
      if($date == null)
         $date = date("Y-m-d-H:i");

      $xml = "<ReceiptId>" . $receipt_id . "</ReceiptId>";
      $xml .= '<NoteDate>' . $date . '</NoteDate>';
      $xml .= '<Notes>' . $note . '</Notes>';

      $response = $this->SendRequest($xml, 'InsertReceiptNote');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->InsertReceiptNoteResponse->Status == 'OK') {
            return (boolean)$response->soapBody->InsertReceiptNoteResponse->InsertReceiptNoteResult;
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertReceiptNoteResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // BANK
   ///////////////////////////////////////////////////

   public function GetBankAccounts()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetBankAccounts');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetBankAccountsResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetBankAccountsResponse->GetBankAccountsResult;

            return $this->clean_dataset($this->object_to_array($array['BankAccount']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankAccountsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetBankBalance($data)
   {
      $xml = '<AccountID>' . $data['AccountID'] . '</AccountID>';
      $xml .= '<BalanceDate>' . $data['BalanceDate'] . '</BalanceDate>';

      $response = $this->SendRequest($xml, 'GetBankBalance');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetBankBalanceResponse->Status == 'OK') {
            return $this->clean_dataset((array)$response->soapBody->GetBankBalanceResponse->GetBankBalanceResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankBalanceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetBankTxTypes()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetBankTxTypes');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetBankTxTypesResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetBankTxTypesResponse->GetBankTxTypesResult;

            return $this->clean_dataset($this->object_to_array($array['BankTXType']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankTxTypes->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // JOURNALS
   ///////////////////////////////////////////////////

   ///////////////////////////////////////////////////
   // REPORTS
   ///////////////////////////////////////////////////

   public function GetAgedDebtors($data)
   {
      $xml = ' <AgedDebtorsDate>' . (string)$data['AgedDebtorsDate'] . '</AgedDebtorsDate>';

      $response = $this->SendRequest($xml, 'GetAgedDebtors');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetAgedDebtorsResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetAgedDebtorsResponse->GetAgedDebtorsResult;

            return $this->clean_dataset($this->object_to_array($array['AgedDebtorsCreditors']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetAgedDebtorsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetBalanceSheet($data)
   {
      $xml = ' <Date>' . (string)$data['Date'] . '</Date>';

      $response = $this->SendRequest($xml, 'GetBalanceSheet');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetBalanceSheetResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetBalanceSheetResponse->GetBalanceSheetResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBalanceSheetResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetDigitaCSVFile($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetDigitaCSVFile');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetDigitaCSVFileResponse->Status == 'OK') {
            return $this->clean_dataset(
               (array)$response->soapBody->GetDigitaCSVFileResponse->GetDigitaCSVFileResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetDigitaCSVFileResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetIncomeByCustomer($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';
      $xml .= ' <BasedOnInvoiceDate>' . (string)$data['BasedOnInvoiceDate'] . '</BasedOnInvoiceDate>';

      $response = $this->SendRequest($xml, 'GetIncomeByCustomer');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetIncomeByCustomerResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetIncomeByCustomerResponse->GetIncomeByCustomerResult;

            return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetIncomeByCustomerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetKPIs($data)
   {
      $xml = ' <AgedDebtorsDate>' . (string)$data['AgedDebtorsDate'] . '</AgedDebtorsDate>';

      $response = $this->SendRequest($xml, 'GetKPIs');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetKPIsResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetKPIsResponse->GetKPIsResult;

            return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetKPIsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetMonthlyProfitAndLoss($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetMonthlyProfitAndLoss');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetMonthlyProfitAndLossResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetMonthlyProfitAndLossResponse->GetMonthlyProfitAndLossResult;

            return $this->clean_dataset($this->object_to_array($array['MonthlyPL']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetMonthlyProfitAndLossResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetNominalLedger($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';
      $xml .= ' <NominalID>' . (int)$data['NominalID'] . '</NominalID>';

      $response = $this->SendRequest($xml, 'GetNominalLedger');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetNominalLedgerResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetNominalLedgerResponse->GetNominalLedgerResult;

            return $this->clean_dataset($this->object_to_array($array['TransactionInformation']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetNominalLedgerResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetProfitAndLoss($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetProfitAndLoss');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetProfitAndLossResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetProfitAndLossResponse->GetProfitAndLossResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetProfitAndLossResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetTrialBalance($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetTrialBalance');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetTrialBalanceResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetTrialBalanceResponse->GetTrialBalanceResult;

            return $this->clean_dataset($this->object_to_array($array['NominalCode']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetTrialBalanceResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetVATReport($data)
   {
      $xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
      $xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

      $response = $this->SendRequest($xml, 'GetVATReport');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetVATReportResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetVATReportResponse->GetVATReportResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetVATReportResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   ///////////////////////////////////////////////////
   // SUPPLEMENTARY FUNCTIONS
   ///////////////////////////////////////////////////

   public function GetAccountOverview()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetAccountOverview');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetAccountOverviewResponse->Status == 'OK') {
            return $this->object_to_array(
               $response->soapBody->GetAccountOverviewResponse->GetAccountOverviewResult
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetAccountOverviewResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetRemoteLoginURL()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetRemoteLoginURL');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetRemoteLoginURLResponse->Status == 'OK') {
            return $this->object_to_array($response->soapBody->GetRemoteLoginURLResponse->GetRemoteLoginURLResult);
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetRemoteLoginURLResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetProducts()
   {
      $xml = '';

      $response = $this->SendRequest($xml, 'GetProducts');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetProductsResponse->Status == 'OK') {
            $array = (array)$response->soapBody->GetProductsResponse->GetProductsResult;

            return $this->clean_dataset($this->object_to_array($array['Product']));
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetProductsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }

   public function GetSubProducts($data)
   {
      // Output needs verification

      $xml = '<NominalID>' . $data['NominalID'] . '</NominalID>';

      $response = $this->SendRequest($xml, 'GetSubProducts');

      if (isset($response->soapBody->soapFault)) {
         $this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

         throw new KashflowException($this->error_msg['ErrorMsg']);
      } else {
         if ($response->soapBody->GetSubProductsResponse->Status == 'OK') {
            return $this->clean_dataset(
               $this->object_to_array($response->soapBody->GetSubProductsResponse->GetSubProductsResult)
            );
         } else {
            $this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetSubProductsResponse->StatusDetail;

            throw new KashflowException($this->error_msg['ErrorMsg']);
         }
      }
   }
}

class KashflowException extends Exception {}

?>