# FIAP_Telegram_Bot [PT_BR]
Bot de Telegram que te avisa sempre que houver mudança no seu boletim.
 - Clone ou baixe este repositório.
 - Execute `php loginRegister.php <RM> <SENHA>` para geração do cache e arquivo de configuração.
 - Edite o config.json inserindo o token e chatid do telegram. Você pode conseguir o token falando com o [@BotFather](https://telegram.me/botfather). Para obter o chatid você pode enviar uma mensagem para o seu bot e acessar o link `api.telegram.org/bot<token>/getUpdates`.
 - Adicione o cron.php no seu /etc/crontab, de acordo com sua preferencia.

Exemplo de uma instrução crontab válida:
`sudo nano /etc/crontab`
`*/5 * * * * root php /home/tabarra/FIAP_Telegram_Bot/cron.php >> /home/tabarra/FIAP_Telegram_Bot/cron.log 2>&1`