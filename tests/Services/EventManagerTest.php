<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EventManager;
use App\Services\Interfaces\ObserverInterface;

class EventManagerTest extends TestCase
{
    private $eventManager;
    private $observer1;
    private $observer2;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();

        // Créer des mocks pour les observateurs
        $this->observer1 = $this->createMock(ObserverInterface::class);
        $this->observer2 = $this->createMock(ObserverInterface::class);
    }

    public function testAttachForEvent(): void
    {
        // Configurer les attentes pour l'observateur
        $this->observer1->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('test.event'),
                $this->equalTo(['data' => 'value'])
            );

        // Attacher l'observateur à un événement spécifique
        $this->eventManager->attachForEvent($this->observer1, 'test.event');

        // Notifier les observateurs
        $this->eventManager->notify('test.event', ['data' => 'value']);
    }

    public function testDetachFromEvent(): void
    {
        // Configurer les attentes pour l'observateur (ne devrait pas être appelé)
        $this->observer1->expects($this->never())
            ->method('update');

        // Attacher puis détacher l'observateur
        $this->eventManager->attachForEvent($this->observer1, 'test.event');
        $this->eventManager->detachFromEvent($this->observer1, 'test.event');

        // Notifier les observateurs
        $this->eventManager->notify('test.event', ['data' => 'value']);
    }

    public function testMultipleObservers(): void
    {
        // Configurer les attentes pour les observateurs
        $this->observer1->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('test.event'),
                $this->equalTo(['data' => 'value'])
            );

        $this->observer2->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('test.event'),
                $this->equalTo(['data' => 'value'])
            );

        // Attacher les observateurs
        $this->eventManager->attachForEvent($this->observer1, 'test.event');
        $this->eventManager->attachForEvent($this->observer2, 'test.event');

        // Notifier les observateurs
        $this->eventManager->notify('test.event', ['data' => 'value']);
    }

    public function testNotifyNonExistentEvent(): void
    {
        // Configurer les attentes pour l'observateur (ne devrait pas être appelé)
        $this->observer1->expects($this->never())
            ->method('update');

        // Attacher l'observateur à un événement
        $this->eventManager->attachForEvent($this->observer1, 'test.event');

        // Notifier pour un événement différent
        $this->eventManager->notify('other.event', ['data' => 'value']);
    }

    public function testAttachSameObserverTwice(): void
    {
        // Configurer les attentes pour l'observateur (ne devrait être appelé qu'une fois)
        $this->observer1->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('test.event'),
                $this->equalTo(['data' => 'value'])
            );

        // Attacher l'observateur deux fois
        $this->eventManager->attachForEvent($this->observer1, 'test.event');
        $this->eventManager->attachForEvent($this->observer1, 'test.event');

        // Notifier les observateurs
        $this->eventManager->notify('test.event', ['data' => 'value']);
    }

    public function testDetachAllEvents(): void
    {
        // Configurer les attentes pour l'observateur (ne devrait pas être appelé)
        $this->observer1->expects($this->never())
            ->method('update');

        // Attacher l'observateur à plusieurs événements
        $this->eventManager->attachForEvent($this->observer1, 'test.event1');
        $this->eventManager->attachForEvent($this->observer1, 'test.event2');

        // Détacher l'observateur de tous les événements
        $this->eventManager->detach($this->observer1);

        // Notifier les observateurs
        $this->eventManager->notify('test.event1', ['data' => 'value']);
        $this->eventManager->notify('test.event2', ['data' => 'value']);
    }

    public function testAttachToAllEvents(): void
    {
        // Créer des événements
        $this->eventManager->attachForEvent($this->observer2, 'test.event1');
        $this->eventManager->attachForEvent($this->observer2, 'test.event2');

        // Configurer les attentes pour l'observateur
        $this->observer1->expects($this->exactly(2))
            ->method('update')
            ->withConsecutive(
                [$this->equalTo('test.event1'), $this->equalTo(['data' => 'value1'])],
                [$this->equalTo('test.event2'), $this->equalTo(['data' => 'value2'])]
            );

        // Attacher l'observateur à tous les événements
        $this->eventManager->attach($this->observer1);

        // Notifier les observateurs
        $this->eventManager->notify('test.event1', ['data' => 'value1']);
        $this->eventManager->notify('test.event2', ['data' => 'value2']);
    }
}
