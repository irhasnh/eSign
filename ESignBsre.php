<?php
// d:\laragon\www\eSign\ESignBsre.php

namespace DiskominfotikBandaAceh\ESignBsrePhp;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class ESignBsre
{
    private $http;
    private $baseUrl;
    private $username;
    private $password;
    private $file;
    private $fileName;
    private $view = 'invisible';
    private $timeout;

    public function __construct($baseUrl, $username, $password, $timeout=30){
        $this->baseUrl = $baseUrl;

        $this->http = new GuzzleClient();
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout; 
    }

    public function setFile($file, $fileName){
        $this->file = $file;
        $this->fileName = $fileName;

        return $this;
    }

    public function setTimeout($timeout){
        $this->timeout = $timeout;

        return $this;
    }

    public function sign($nik, $passphrase)
    {
        $esignResponse = new ESignBsreResponse(); // Buat objek respons di awal
        $response = null; // Inisialisasi respons Guzzle menjadi null

        try {
            $response = $this->http->request('POST', "{$this->getBaseUrl()}/api/sign/pdf", [
                'auth' => $this->getAuth(),
                'timeout' => $this->timeout,
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => $this->file,
                        'filename' => $this->fileName
                    ],
                    [
                        'name'     => 'nik',
                        'contents' => $nik,
                    ],
                    [
                        'name'     => 'passphrase',
                        'contents' => $passphrase,
                    ],
                    [
                        'name'     => 'tampilan',
                        'contents' => $this->view,
                    ],
                ],
            ]);

            // Jika tidak ada exception, proses respons sukses
            $esignResponse->setFromResponse($response);

        }catch(ConnectException $e){
            // Tangani Connection Exception
            $esignResponse->setFromExeption($e, ESignBsreResponse::STATUS_TIMEOUT);
        } catch (RequestException $e) {
            // Tangani Request Exception (misalnya 4xx atau 5xx dari API)
            $response = $e->getResponse(); // Dapatkan respons Guzzle dari RequestException
            if ($response) { // Pastikan respons ada sebelum memprosesnya
                 $esignResponse->setFromResponse($response);
            } else { // Jika tidak ada respons dalam exception (jarang terjadi)
                 $esignResponse->setFromExeption($e, $e->getCode() > 0 ? $e->getCode() : 500); // Gunakan kode error jika ada, default 500
            }
        } catch (\Exception $e) {
            // Tangani exception umum lainnya
            $esignResponse->setFromExeption($e, 500); // Default status 500 untuk error tidak terduga
        }

        return $esignResponse; // Kembalikan objek respons yang sudah diisi
    }

    public function verification()
    {
        $esignResponse = new ESignBsreResponse(); // Buat objek respons di awal
        $response = null; // Inisialisasi respons Guzzle menjadi null

        try {
            $response = $this->http->request('POST', "{$this->getBaseUrl()}/api/sign/verify", [
                'auth' => $this->getAuth(),
                'timeout' => $this->timeout,
                'multipart' => [
                    [
                        'name' => 'signed_file',
                        'contents' => $this->file,
                        'filename' => $this->fileName
                    ],
                ]
            ]);

            // Jika tidak ada exception, proses respons sukses
            $esignResponse->setFromResponse($response);

        }catch(ConnectException $e){
            // Tangani Connection Exception
            $esignResponse->setFromExeption($e, ESignBsreResponse::STATUS_TIMEOUT);
        } catch (RequestException $e) {
            // Tangani Request Exception
            $response = $e->getResponse();
             if ($response) {
                 $esignResponse->setFromResponse($response);
            } else {
                 $esignResponse->setFromExeption($e, $e->getCode() > 0 ? $e->getCode() : 500);
            }
        } catch (\Exception $e) {
            // Tangani exception umum
            $esignResponse->setFromExeption($e, 500);
        }

        return $esignResponse; // Kembalikan objek respons yang sudah diisi
    }

    public function statusUser($nik) {
        $esignResponse = new ESignBsreResponse(); // Buat objek respons di awal
        $response = null; // Inisialisasi respons Guzzle menjadi null

        try {
            $response = $this->http->request('GET', "{$this->getBaseUrl()}/api/user/status/$nik", [
                'auth' => $this->getAuth(),
                'timeout' => $this->timeout,
            ]);

            // Jika tidak ada exception, proses respons sukses
            $esignResponse->setFromResponse($response);

        } catch(ConnectException $e){
            // Tangani Connection Exception
            $esignResponse->setFromExeption($e, ESignBsreResponse::STATUS_TIMEOUT);
        } catch (RequestException $e) {
             // Tangani Request Exception
            $response = $e->getResponse();
             if ($response) {
                 $esignResponse->setFromResponse($response);
            } else {
                 $esignResponse->setFromExeption($e, $e->getCode() > 0 ? $e->getCode() : 500);
            }
        } catch (\Exception $e) {
            // Tangani exception umum
            $esignResponse->setFromExeption($e, 500);
        }

        return $esignResponse; // Kembalikan objek respons yang sudah diisi
    }

    private function getAuth(){
        return [$this->username, $this->password];
    }

    private function getBaseUrl(){
        return rtrim($this->baseUrl, "/");
    }
}