# Laravel Event Store Package

This package presents the necessary tools to leverage Prooph Event Stores inside
a Laravel project.

An example of how to use this project can be found at the [ProophessorDo Laravel example](https://github.com/camuthig/proophessor-do-laravel).

## Features

- [x] Support PDO event stores out of the box
- [x] Provide commands for working with projections
- [x] Bind repositories to class name and optional interface
- [x] Support custom projection manager and event store resolvers
- [x] Support snapshot stores
- [x] Add migrations for streams, projections and snapshots

## Installation

`composer require camuthig/laravel-event-store-package`

## Setup

### Publish the Config

`php artisan vendor:publish`

### Include the Provider

The package will automatically be discovered by Laravel when installed, no
changes to include the service provider are needed.

## Usage

### Event Store

Each event store is bound in a number of ways.

* Each store is bound to the class it implements
* The `default` store will also be bound the the `EventStore` interface

Additionally, each store can be retrieved by name, as found in the configuration file,
using the `EventStoreManager` or `EventStore` facade.


```php
<?php

use Camuthig\EventStore\Package\EventStoreManager;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;

class MyController
{
    public function __construct(EventStore $eventStore, MySqlEventStore $mySqlEventStore, EventStoreManager $eventStoreManager) 
    {
        // The EventStore interface will be bound to the "default" store
        $eventStore->fetchCategoryNames(null);
        
        // Each event store is also bound to the class it is an instance of
        $mySqlEventStore->fetchCategoryNames(null);
        
        // The EventStoreManager is also bound into the application
        
        // The default event store can be retrieved from the EventStoreManager
        $eventStoreManager->store()->fetchCategoryNames(null);
        
        // Or you can fetch any store by name
        $eventStoreManager->store('postgres')->fetchCategoryNames(null);
        
        // Or you can work wih the manager by facade
        \Camuthig\EventStore\Package\Facade\EventStore::store()->fetchCategoryNames(null);
    }
}
```

### Repositories

Each repository will be bound to the `repository_class` defined in the
configuration file. Additionally, you can provide a `repository_interface`. If
provided, the instance will also be bound to the interface, which can be used
for dependency injection.

### Projection Managers

Projection managers are configured and bound to the Artisan application. The
package defines a number of commands to work with the projection managers to
accomplish tasks like:

* Running a projection
* Deleting a projection
* Reset a projection
* Stopping a projection
* Showing the status of a projection
* Showing the event stream positions for a projection
* Listing all available projections

To use the commands, first start a projection by name:

`php artisan event-store:projection:run users`

While the projection is running, you can delete/reset/stop it:

`php artisan event-store:projection:reset users`

`php artisan event-store:projection:delete -w users`

`php artisan event-store:projection:stop users`

## Configuration

### Plugins

A list of globally available Event Store plugins. Each entry will be the
registered service ID for the plugin. The application will `app()->make(<name>)`
for each entry in the list.

```php
[
    App\EventStore\Plugins\MyPlugin::class,
]

```

### Metadata Enrichers

A list of globally available Event Store metadata enrichers. Each entry
will be the registered service ID for the plugin. The application will
`app()->make(<name>)` for each entry in the list.

```php
[
    App\EventStore\Enrichers\MyEnricher::class,
]

```

### Stores

A list of all defined event stores in the system. The plugin currently supports
the following stores out of the box:

* MySQL
* MariaDB
* PostgreSQL

Each Event Store can configure:

* **persistence_strategy** The class name or service ID of the persistence
strategy 
* **load_batch_size** The number of events a query should return in a
single batch. Default is 1000. 
* **event_streams_table** The event stream table to use. The default is
event_streams.
* **message_factory** The message factory to use. Default is FCQNMessageFactory 
* **disable_transaction_handling** Boolean to turn off transaction handling. The
default is false.
* **action_event_emitter** The default is ProophEventActionEmitter.
* **wrap_action_event_emitter** The default is true. 
* **metadata_enrichers** A list of metadata enrichers to add to the store. 
* **plugins** A list of plugins
* to add to the store.

#### Configuring multiple stores for the same database engine

You may, for example want to use different stores for different purposes. An example of this is using a store with a
different `persistence_strategy` for running migrations, which is much more efficient 
([see this issue](https://github.com/prooph/pdo-event-store/issues/224)).

You must name the store starting with the name of the engine (for example, `mysql`, followed by a suffix (for example
`_projections`. The package will check what the store name starts with to determine the engine to use.

Example Config:
```php
return [
    'stores' => [
        'default' => 'mysql',
        'mysql' => [
            'persistence_strategy' => \Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSingleStreamStrategy::class,
        ],
        'mysql_projection' => [
            'persistence_strategy' => \Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSimpleStreamStrategy::class,
        ],
    ],

    'repositories' => [
        'user_collection' => [
            'store'                => 'mysql', // Use store with `MySqlSingleStreamStrategy` for fast aggregate lookups
            'repository_interface' => UserCollection::class,
            'repository_class'     => EventStoreUserCollection::class,
            'aggregate_type'       => User::class,
        ],
    ],
    'projection_managers' => [
        'default_projection_manager' => [
            'store' => 'mysql_projection', // Use different store with `MySqlSimpleStreamStrategy`
            'projections' => [
                'user_projection' => [
                    'read_model' => UserReadModel::class,
                    'projection' => UserProjection::class,
                ],
            ],
        ],
    ],
];
```

### Repositories

A list of all defined repositories. Each repository should be indexed using
a name. Each repository should define:

* **store** The key of the store to use. Valid values are any key in the
`stores` array above. 
* **repository_interface** An optional interface to alias the repository with.
This can be used to support dependency injection in your classes.
* **repository_class** The FQCN or service ID for the repository class. 
* **aggregate_type** The FQCN for the aggregate this store maintains or the
array mapping.
* **aggregate_translator** The translator for the aggregate. Defaults
to \Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator.
* **stream_name** The stream name. 
* **one_stream_per_aggregate** Set this to 
true for an aggregate stream strategy. Default is false.

### Projection Managers

A list of all configured projection managers. Each manager should define: 

* **store** The name of the store. One of mysql, maria_db or postgres
* **event_streams_table** Defaults to event_streams
* **projections_table** Defaults to projections
* **projections** A list of all projections in this manager.

Each projection should define:

* **connection** The name of the connection to use. This is an optional
configuration. Defaults to the same connection as the store if not provided.
* **read_model** The FQCN or service ID of the projection read model. This 
is an optional value, only necessary for Read Model projections.
* **projection** The FQCN or service ID of the projection.
