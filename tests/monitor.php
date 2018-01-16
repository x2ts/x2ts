<?php

$con = new AMQPConnection();
$con->connect();
$chn = new AMQPChannel($con);

$x = new AMQPExchange($chn);
$x->setType(AMQP_EX_TYPE_TOPIC);
$x->setName('log');
$x->declareExchange();

$q = new AMQPQueue($chn);
$q->setFlags(AMQP_EXCLUSIVE);
$q->declareQueue();
$q->bind('log', 'logger2.*');

$q->consume(function (AMQPEnvelope $envelope, AMQPQueue $q) {
    echo $envelope->getBody();
    $q->ack($envelope->getDeliveryTag(), AMQP_NOPARAM);
    return true;
});
