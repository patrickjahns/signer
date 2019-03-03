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

namespace Signer\Command;

use Signer\Security\JWTSecurity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTokenCommand extends Command
{
    protected static $defaultName = 'signer:create-token';

    /**
     * @var JWTSecurity
     */
    private $security;

    public function __construct(JWTSecurity $security)
    {
        $this->security = $security;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a token')
            ->setHelp('This command allows to create new JWT to be used with the signer')
            ->addArgument('subject', InputArgument::REQUIRED, 'subject for whom the token is issued')
            ->addArgument('claims', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'claims for the token')
            ->addOption('valid', null, InputOption::VALUE_REQUIRED, 'amount of seconds the token is valid [default: 315360000 seconds]', 315360000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $claims = $input->getArgument('claims');
        $subject = $input->getArgument('subject');
        $valid = $input->getOption('valid');
        $token = $this->security->issueToken($claims, $valid, 'signer-cli', $subject);
        $output->writeln($token);
    }
}
