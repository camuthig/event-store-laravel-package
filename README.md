# Laravel Event Store Package

This package presents the necessary tools to leverage Prooph Event Stores inside
a Laravel project.

## Features

[x] Support PDO event stores out of the box
[x] Provide commands for working with projections
[x] Bind repositories to class name and optional interface
[x] Support custom projection manager and event store resolvers
[x] Support snapshot stores

## Installation

`composer require camuthig/laravel-event-store-package`

## Usage

### Event Store

Each store will be bound to the class it defines. Additionally, the store
defined as the `default` will be bound to the `EventStore` interface.

An `EventStoreManager` will also be bound into the application. Each event store
can be retrieved from the manager using
`app()->make(EventStoreManager)->store($name)`.

### Repositories

Each repository will be bound to the `repository_class` defined in the
configuration file. Additionally, you can provider a `repository_interface`. If
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


## Setup

### Publish the Config

`php artisan vendor:publish`

### Include the Provider

The package will automatically be discovered by Laravel when installed, no
changes to include the service provider are needed.

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
* **stream_name** The stream name. **one_stream_per_aggregate** Set this to 
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
