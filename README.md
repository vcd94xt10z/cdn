# CDN
Sistema para Content Delivery Network (CDN)

## Pré Requisitos
- Apache >= 2.2
- PHP >= 5.6

## Instalação

- Clone o projeto
- Copie o arquivo sample-cdn.json para cdn.json e configure a origem
- Instale o certificado ssl no servidor se usar ssl. Caso não informe, o padrão será utilizado 
- Pronto, sua CDN já estará funcionando nesse servidor (Edge Node)
- Crie um registro do tipo A ou CNAME no servidor DNS do seu domínio apontando para o endereço da CDN
- O cache será gravado dentro do diretório "/tmp/cache/<domínio>/" do projeto

## Como Usar

Para simplificar a utilização da CDN, o unico modo de funcionamento é usando o cabeçalho max-age e s-maxage.

Exemplo:

Cache no navegador de 60s e na CDN de 120s
 
```php
max-age=60, s-maxage=120
```

Cache no navegador de 0s e na CDN de 60s
 
```php
max-age=0, s-maxage=60
```

## Limitações

Para que a a CDN funcione completamente, isso tem que ser feito em cada Edge Node, por exemplo:
Você tem um servidor nos EUA e no Brasil, a instalação tem que ser feita nesses dois lugares.

Lembrando que o roteamento não é feito por este aplicativo, o sistema não vai identificar qual o Edge Nodes 
mais próximo do seu cliente e encaminhar o cliente para lá.

Isso deve ser feito por outro software, é provavel que a AWS já tenha essa solução pronta, provavelmente alguma coisa
relacionada a LoadBalancer regional.

Esta CDN esta em fase de desenvolvimento, use por sua conta e risco. Mais informações, leia a licença

## Alterações futuras
- Apagar cache frio
- Ajuste método POST, PUT etc
- Painel de administração
- Painel de estatisticas da CDN