<?php

namespace EasyWeChat\Kernel\Traits;

use EasyWeChat\Kernel\Encryptor;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Message;
use EasyWeChat\Kernel\Support\Xml;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

trait RespondXmlMessage
{
    /**
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function transformToReply($response, Message $message, ?Encryptor $encryptor = null): ResponseInterface
    {
        if (empty($response)) {
            return new Response(200, [], 'success');
        }

        return $this->createXmlResponse(
            attributes: array_filter(
                \array_merge(
                    [
                        'ToUserName' => $message->FromUserName,
                        'FromUserName' => $message->ToUserName,
                        'CreateTime' => \time(),
                    ],
                    $this->normalizeResponse($response),
                )
            ),
            encryptor: $encryptor
        );
    }

    /**
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    protected function normalizeResponse(mixed $response): array
    {
        if (\is_array($response)) {
            if (!isset($response['MsgType'])) {
                throw new InvalidArgumentException('MsgType cannot be empty.');
            }

            return $response;
        }

        if (is_string($response) || is_numeric($response)) {
            return [
                'MsgType' => 'text',
                'Content' => $response,
            ];
        }

        throw new InvalidArgumentException(
            sprintf('Invalid Response type "%s".', gettype($response))
        );
    }

    /**
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    protected function createXmlResponse(array $attributes, ?Encryptor $encryptor = null): ResponseInterface
    {
        $xml = Xml::build($attributes);

        $response = new Response(200, ['Content-Type' => 'application/xml'], $encryptor ? $encryptor->encrypt($xml) : $xml);

        $response->getBody()->rewind();

        return $response;
    }
}