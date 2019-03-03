<?php

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 * @copyright Copyright (c) 2019, Patrick Jahns.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Signer\EventSubscribers;

use Signer\Exception\AppException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();
        if ($e instanceof HttpException) {
            $response = new JsonResponse([
                    'error' => $this->getStatusText($e->getStatusCode()),
                    'code' => $e->getStatusCode(),
                ],
                $e->getStatusCode(),
                $e->getHeaders()
            );

            $event->setResponse($response);

            return;
        }

        if ($e instanceof AppException) {
            $response = new JsonResponse([
                'error' => $e->getMessage(),
                'code' => $e->getStatusCode(),
                ],
                $e->getStatusCode(),
            );

            $event->setResponse($response);

            return;
        }

        $response = new JsonResponse(
            [
                'error' => $this->getStatusText(Response::HTTP_INTERNAL_SERVER_ERROR),
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
        $event->setResponse($response);
    }

    private function getStatusText(int $statusCode)
    {
        if (!array_key_exists($statusCode, Response::$statusTexts)) {
            return null;
        }

        return Response::$statusTexts[$statusCode];
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
