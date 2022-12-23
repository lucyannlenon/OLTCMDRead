# OLTCMDRead

```

    $userName = 'xxx';
    $password = 'xxx';
    $address = '192.168.x.x';
    $tipoConnection = "telnet";
    $model = OltModel::VSOL;
    $port = 23;
    $olt = new OLT($userName, $password, $model, $address, $port, $tipoConnection);
    $macAddress = "xx:xx:xx:xx:xx:xx";
    $login = "teste";
    $gponName = "testeteste";


    $client = new Client($login, $macAddress, $gponName);

    $oltVsol = new VSolOLTCmd($olt, $client);
    dd($oltVsol->getDadosDoCliente());
    
    /**
    {#2
  +login: "teste"
  +macAddress: "xx:xx:xx:xx:xx:xx"
  +gponName: "testeteste"
  +slot: "0"
  +onuId: "29"
  +pon: "2"
  +signal: "-21.19"
  +status: "online"
  +distance: "1215"
  +uptime: "8 20:25:32"
  +onuTemperatura: "41.98"
}*/
```
re

