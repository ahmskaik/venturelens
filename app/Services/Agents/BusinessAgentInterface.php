<?php

namespace App\Services\Agents;

interface BusinessAgentInterface
{
    public function name(): string;

    public function run(): AgentResult;
}
