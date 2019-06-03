<?php

class FastPress_SessionStore
{

    private $context;

    private $sessionsKey = 'fastpress_sessions';

    public function __construct(FastPress_Context $context)
    {
        $this->context = $context;
    }

    public function add($userId, $token)
    {
        $sessions                  = $this->getSessions();
        $sessions[(int) $userId][] = (string) $token;

        $this->saveSessions($sessions);
    }

    /**
     * @return int Number of destroyed sessions.
     */
    public function destroyAll()
    {
        if (!$this->context->isVersionAtLeast('4.0.0')) {
            return -1;
        }

        $removed = 0;
        foreach ($this->getSessions() as $userId => $tokens) {
            $sessionTokens = $this->context->getSessionTokens($userId);
            foreach ($tokens as $token) {
                $sessionTokens->destroy($token);
                $removed++;
            }
        }

        $this->saveSessions(array());

        return $removed;
    }

    private function getSessions()
    {
        $sessions = $this->context->transientGet($this->sessionsKey);

        return $sessions ? $sessions : array();
    }

    private function saveSessions($sessions)
    {
        $this->context->transientSet($this->sessionsKey, $sessions);
    }
}
