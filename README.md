# qrator [![Build Status](https://travis-ci.org/watoki/qrator.png?branch=master)](https://travis-ci.org/watoki/qrator)

Administration interface using the [Command Object][commandobject] pattern.

For a detailled overview of *qrator*'s features and API, check out its [executable documentation][dox].

[commandobject]: http://c2.com/cgi/wiki?CommandObject
[dox]: http://dox.rtens.org/projects/watoki-qrator
[demo]: http://github.com/rtens/qrator-demo


## Status

The project completely functional but still under heavy development which is driven by a project I'm currently working on. Once I deem *qrator* as stable enough I'll tag a release and provide some documentation on how to use it.

Until then check out the [Demo project][demo].


## The Why

A couple of weeks ago a client asked me to find a general purspose adminstiration interface that could be used for several projects. But I couldn't find a single one that wouldn't just bypass my application and domain layers completely and talk directly to the storage system.

So I started designing an admin interface that was compatible with [Domain-Driven Design][ddd] and hence could also manage entities in projects that implemented [Command/Query Segregation][cqrs] or [Event Sourcing][eventsourcing]. Born was *qrator*.

[ddd]: http://en.wikipedia.org/wiki/Domain-driven_design
[cqrs]: http://martinfowler.com/bliki/CQRS.html
[eventsourcing]: http://martinfowler.com/eaaDev/EventSourcing.html

## Concepts

These are the building blocks and their meanings in *qrator*.

### Entity

An entity is any class that represents a part of your system whose state you would like to manage. Its properties are derived from the instance variables and accessor methods and displayed whenever one or several entities is the result of an action.

### Action

Actions are *something you can do with an entity*. They are always attached to some entity and displayed whenever the entity is displayed. If an action returns something, it is displayed as the query's result.

Usually, an action either changes the state of an entity and returns nothing (i.e. Command) or returns one or several entities but changes no state (i.e. Query).

Actions can have properties as well which are rendered as form fields for the user to fill out if neccessary.

### Representer

Since Entities and Actions belong to the Domain layer and are thus independent of *qrator*, [EntityRepresenter]s and [ActionRepresenter]s bind them together. It knows what Actions belong to what Entities, how to instanciate and execute them, how to print them and so on.

### Registry

The [RepresenterRegistry] knows what Representers represent which class of the Domain layer. It is the single place for the whole *qrator* configuration.


[EntityRepresenter]: https://github.com/watoki/qrator/blob/master/src/watoki/qrator/EntityRepresenter.php
[ActionRepresenter]: https://github.com/watoki/qrator/blob/master/src/watoki/qrator/ActionRepresenter.php
[RepresenterRegistry]: https://github.com/watoki/qrator/blob/master/src/watoki/qrator/RepresenterRegistry.php
