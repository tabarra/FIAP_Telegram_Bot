<?php
require('functions.php');

function printUsage(){
    echo '== FIAP Telegram Bot configurator =='.PHP_EOL;
    echo 'Usage: php loginRegister.php <rm> <senha>'.PHP_EOL;
    echo PHP_EOL;
    exit;
}

//Checando argumentos
if(count($argv) != 3 || !ctype_digit($argv[1]) || !ctype_digit($argv[2])){
    printUsage();
}
$rm = $argv[1];
$senha = $argv[2];

$loginXML = '<Login xmlns="http://tempuri.org/" id="o0" c:root="1">
    <inRM i:type="d:string">__RM__</inRM>
    <stSenha i:type="d:string">__SENHA__</stSenha>
    <stChave i:type="d:string">DD114BA7-FCB6-4638-88DB-F7E0FF57F894</stChave>
    <inVersao i:type="d:string">1</inVersao>
</Login>';

$loginXML = str_replace('__RM__', $argv[1], $loginXML);
$loginXML = str_replace('__SENHA__', $argv[2], $loginXML);

echo 'Efetuando login...'.PHP_EOL.PHP_EOL;
$loginData = getJSON('Login', $loginXML);
if($loginData === false) die('Erro XML ou senha incorreta.'.PHP_EOL);
$loginData = @json_decode($loginData);
if(!is_object($loginData)) die('Erro decodificando JSON'.PHP_EOL);

echo "NomeAluno: {$loginData->NomeAluno}".PHP_EOL;
echo "Email: {$loginData->Email}".PHP_EOL;
echo "ChaveAluno: {$loginData->ChaveAluno}".PHP_EOL;
echo "Turmas:".PHP_EOL.PHP_EOL;


foreach ($loginData->Turmas as $key => $turma) {
    echo "    {$key}:  {$turma->NomeTurma}/{$turma->Ano} - {$turma->Curso}".PHP_EOL;
    $turmaMax = $key;
}

$turmaID = readline("Digite o número da turma(0-{$turmaMax}): ");
if(!ctype_digit($turmaID) || $turmaID > $turmaMax){
    die("Numero inválido.");
}

//Objeto de config
echo PHP_EOL.'Gerando e salvando config.json...'.PHP_EOL;
$configJSON = new stdClass();
$configJSON->rm = $loginData->RM;
$configJSON->ano = $loginData->Turmas[$turmaID]->Ano;
$configJSON->turma = $loginData->Turmas[$turmaID]->NomeTurma;
$configJSON->chave = $loginData->ChaveAluno;
$configJSON->telegram_chatid = 'chatid';
$configJSON->telegram_token = 'token';
$configJSON = json_encode($configJSON, JSON_PRETTY_PRINT);
file_put_contents('config.json', $configJSON);




$boletimXML = '<Boletim xmlns="http://tempuri.org/" id="o0" c:root="1">
    <inRM i:type="d:string">__RM__</inRM>
    <inAno i:type="d:string">__ANO__</inAno>
    <stTurma i:type="d:string">__TURMA__</stTurma>
    <stChaveAluno i:type="d:string">__CHAVE__</stChaveAluno>
    <inVersao i:type="d:string">1</inVersao>
</Boletim>';

$boletimXML = str_replace('__RM__', $loginData->RM, $boletimXML);
$boletimXML = str_replace('__ANO__', $loginData->Turmas[$turmaID]->Ano, $boletimXML);
$boletimXML = str_replace('__TURMA__', $loginData->Turmas[$turmaID]->NomeTurma, $boletimXML);
$boletimXML = str_replace('__CHAVE__', $loginData->ChaveAluno, $boletimXML);

echo 'Acessando boletim...'.PHP_EOL;
$boletimData = getJSON('Boletim', $boletimXML);
if($boletimData === false) die('Erro XML ou dados incorretos.'.PHP_EOL);
$boletimData = @json_decode($boletimData);
if(!is_object($boletimData)) die('Erro decodificando JSON'.PHP_EOL);


//Objeto de cache
echo 'Gerando e salvando cache.json...'.PHP_EOL;
$cacheJSON = new stdClass();
foreach ($boletimData->ListaNotas as $materia) {
    $cacheJSON->{$materia->CodigoRelacao} = $materia;
}
$cacheJSON = json_encode($cacheJSON, JSON_PRETTY_PRINT);
file_put_contents('cache.json', $cacheJSON);
echo 'Altere `telegram_chatid` e `telegram_token` no config.json antes de executar o cron.php...'.PHP_EOL;