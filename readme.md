**A PHP Document Model**

*Version 1.0 Beta*

A system for storing document information and rendering tags and collections
of tags. This allows for a modular approach to manipulating and rendering
various components of an HTML document.

The model consists of three components:

- a containers class that extends ArrayObject
- an abstract document class with properties, containers, and business logic
- a document class that extends abstractDocument and contains rendering methods