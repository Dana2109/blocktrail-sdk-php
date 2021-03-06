<?php

namespace Blocktrail\SDK\Tests;


use BitWasp\Bitcoin\Address\ScriptHashAddress;
use Blocktrail\SDK\Address\BitcoinCashAddressReader;
use Blocktrail\SDK\Address\CashAddress;
use Blocktrail\SDK\Network\BitcoinCashTestnet;
use Blocktrail\SDK\Exceptions\BlocktrailSDKException;
use Blocktrail\SDK\Network\BitcoinCash;

class BitcoinCashAddressTest extends BlocktrailTestCase
{
    /**
     * @expectedException \Blocktrail\SDK\Exceptions\BlocktrailSDKException
     * @expectedExceptionMessage Address not recognized
     */
    public function testShortCashAddress() {
        $bch = new BitcoinCash();
        $tbch = new BitcoinCashTestnet();

        $address = "bchtest:ppm2qsznhks23z7629mms6s4cwef74vcwvhanqgjxu";
        $short = "ppm2qsznhks23z7629mms6s4cwef74vcwvhanqgjxu";
        $reader = new BitcoinCashAddressReader(true);
        $this->assertEquals($address, $reader->fromString($address, $bch)->getAddress($bch));
        $this->assertEquals($address, $reader->fromString($short, $bch)->getAddress($bch));

        $this->setExpectedException(BlocktrailSDKException::class, "Address not recognized");
        $reader->fromString($short, $tbch);
    }

    public function testInitializeWithDefaultFormat() {
        $isTestnet = true;
        $tbcc = new BitcoinCashTestnet();
        $client = $this->setupBlocktrailSDK("BCC", $isTestnet);
        $legacyAddressWallet = $client->initWallet([
            "identifier" => "unittest-transaction",
            "password" => "password"
        ]);

        $legacyAddress = "2N44ThNe8NXHyv4bsX8AoVCXquBRW94Ls7W";
        $this->assertInstanceOf(BitcoinCashAddressReader::class, $legacyAddressWallet->getAddressReader());
        $this->assertInstanceOf(ScriptHashAddress::class, $legacyAddressWallet->getAddressReader()->fromString($legacyAddress, $tbcc));

        $cashAddress = "bchtest:ppm2qsznhks23z7629mms6s4cwef74vcwvhanqgjxu";
        $newAddressWallet = $client->initWallet([
            "identifier" => "unittest-transaction",
            "password" => "password",
            "use_cashaddress" => true,
        ]);

        $this->assertInstanceOf(BitcoinCashAddressReader::class, $newAddressWallet->getAddressReader());
        $this->assertInstanceOf(CashAddress::class, $newAddressWallet->getAddressReader()->fromString($cashAddress, $tbcc));

        $convertedLegacy = $client->getLegacyBitcoinCashAddress($cashAddress);

        $this->assertEquals($legacyAddress, $convertedLegacy);
    }

    public function testCurrentDefaultIsOldFormat() {
        $isTestnet = true;
        $tbcc = new BitcoinCashTestnet();

        $client = $this->setupBlocktrailSDK("BCC", $isTestnet);
        $cashAddrWallet = $client->initWallet([
            "identifier" => "unittest-transaction",
            "password" => "password",
            "use_cashaddress" => false,
        ]);

        $newAddress = $cashAddrWallet->getNewAddress();

        $reader = $cashAddrWallet->getAddressReader();
        $this->assertInstanceOf(ScriptHashAddress::class, $reader->fromString($newAddress, $tbcc));
    }

    public function testCanOptIntoNewAddressFormat() {
        $isTestnet = true;
        $tbcc = new BitcoinCashTestnet();

        $client = $this->setupBlocktrailSDK("BCC", $isTestnet);
        $cashAddrWallet = $client->initWallet([
            "identifier" => "unittest-transaction",
            "password" => "password",
            "use_cashaddress" => true,
        ]);

        $newAddress = $cashAddrWallet->getNewAddress();

        $reader = $cashAddrWallet->getAddressReader();
        $this->assertInstanceOf(CashAddress::class, $reader->fromString($newAddress, $tbcc));
    }

    public function testCanCoinSelectNewCashAddresses()
    {
        $isTestnet = true;
        $tbcc = new BitcoinCashTestnet();
        $client = $this->setupBlocktrailSDK("BCC", $isTestnet);
        $cashAddrWallet = $client->initWallet([
            "identifier" => "unittest-transaction",
            "password" => "password",
            "use_cashaddress" => true,
        ]);

        $str = "bchtest:ppm2qsznhks23z7629mms6s4cwef74vcwvhanqgjxu";
        $cashaddr = $cashAddrWallet->getAddressReader()->fromString($str, $tbcc);

        $selection = $cashAddrWallet->coinSelection([
             $cashaddr->getAddress($tbcc) => 1234123,
        ], false);

        $this->assertArrayHasKey('utxos', $selection);
        $this->assertTrue(count($selection['utxos']) > 0);
    }
}
