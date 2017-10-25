<?php
chdir(dirname(__FILE__));
require('functions.php');


//////////////////////////////////////////////////////////////////////////////////////////////
//Checar configuracoes e cache
//////////////////////////////////////////////////////////////////////////////////////////////
if(!is_readable('cache.json') || !is_readable('config.json')){
    die('Nao foi possivel ler config.json e/ou cache.json, execute loginRegister.php'.PHP_EOL);
}

$configData = @json_decode(file_get_contents('config.json'));
if(!is_object($configData) || empty($configData)) die('Nao foi possivel ler config.json, execute loginRegister.php'.PHP_EOL);
$cacheData = @json_decode(file_get_contents('cache.json'));
if(!is_object($cacheData) || empty($cacheData)) die('Nao foi possivel ler cache.json, execute loginRegister.php'.PHP_EOL);


//////////////////////////////////////////////////////////////////////////////////////////////
//Baixar boletim
//////////////////////////////////////////////////////////////////////////////////////////////
$boletimXML = '<Boletim xmlns="http://tempuri.org/" id="o0" c:root="1">
    <inRM i:type="d:string">__RM__</inRM>
    <inAno i:type="d:string">__ANO__</inAno>
    <stTurma i:type="d:string">__TURMA__</stTurma>
    <stChaveAluno i:type="d:string">__CHAVE__</stChaveAluno>
    <inVersao i:type="d:string">1</inVersao>
</Boletim>';

$boletimXML = str_replace('__RM__', $configData->rm, $boletimXML);
$boletimXML = str_replace('__ANO__', $configData->ano, $boletimXML);
$boletimXML = str_replace('__TURMA__', $configData->turma, $boletimXML);
$boletimXML = str_replace('__CHAVE__', $configData->chave, $boletimXML);

$boletimData = getJSON('Boletim', $boletimXML);
if($boletimData === false) die('Erro XML ou dados incorretos.'.PHP_EOL);
$boletimData = @json_decode($boletimData);
if(
    !is_object($boletimData) ||
    !property_exists($boletimData, 'ListaNotas') ||
    !is_array($boletimData->ListaNotas) ||
    empty($boletimData->ListaNotas)
) {
    die('Erro decodificando JSON'.PHP_EOL);
}
$materias = &$boletimData->ListaNotas;


//////////////////////////////////////////////////////////////////////////////////////////////
//Comparar boletim com cache
//////////////////////////////////////////////////////////////////////////////////////////////
$alteradas = [];
foreach ($materias as $materia) {
    if(
        !property_exists($cacheData, $materia->CodigoRelacao) ||
        $materia != $cacheData->{$materia->CodigoRelacao}
    ){
        $alteradas[] = $materia->Disciplina;
    }
}
if(empty($alteradas)) die('Sem alteracoes no boletim'.PHP_EOL);
echo 'Alteracoes encontradas, enviando telegram'.PHP_EOL;


//////////////////////////////////////////////////////////////////////////////////////////////
//Compondo e enviando telegram
//////////////////////////////////////////////////////////////////////////////////////////////
$mensagem = "*Alterações na(s) matéria(s):* " . implode(', ', $alteradas);
$resp = sendTelegram($mensagem, $configData->telegram_chatid, $configData->telegram_token);
if($resp->ok !== true) die('erro api telegram'.PHP_EOL);


//////////////////////////////////////////////////////////////////////////////////////////////
//Gerando e salvando cache
//////////////////////////////////////////////////////////////////////////////////////////////
echo 'Gerando e salvando cache.json...'.PHP_EOL;
$cacheJSON = new stdClass();
foreach ($materias as $materia) {
    $cacheJSON->{$materia->CodigoRelacao} = $materia;
}
$cacheJSON = json_encode($cacheJSON, JSON_PRETTY_PRINT);
file_put_contents('cache.json', $cacheJSON);
