# The Tejas PHP MVC Framework

The inspiration to build this framework came from nodejs. I have been building MEAN applications for a while now and found the simplicity of Nodejs to build CRUD type applications just at another level. Having built several applications with a predefined structure in the MEAN stack thought if this could be replicated in PHP and Viola the Tejas PHP MVC Framework was born.

The framework consists of the following parts:

1. Database Schema
2. Routes
3. Controllers
4. Utlities

## Database Schema

Schema definition are done in schema files. Look in the schema directory for an example. This framework and subsequently Tejas supports nested database objects, similar to documents in mongodb.

## Routes

Routes are defined similar to route definitions in MEAN applications

## Controllers

Controllers are where the primary business logic of the application resides. This is where schema objects are used to perform database operations.

## Utilities

Utility classes contain helper functions which are used throughout the framework.

__P.S. This framework is a heavy work in progress so please do not use it in production and absolutely not in case you are not sure what you are doing__
