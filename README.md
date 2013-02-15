surveyapp
=========

The main purpose of this proyect is to experiment with different technologies.

This is an application to generate custom surveys, polls, contests and presentations.

It's based on the concept of an activities flow or process.

Each activity could be a question, a content or a flash/video. The application is designed with an open architechture in order to allow expansion (aka. plugins). An activity it's just an abstraction of a step within a process.
Processes can be customized using a theme, which is a group of resources like images, styles, html, etc.

The first version use a user batch manager, which allow load participants for the processes. The system record each participant performance and track its performance.

Technologies
------------

* PHP
* CodeIgniter
* Php_Tal
* MongoDb
* JQuery/Backbone/Underscore
* Bootstrap
* ElFinder


Demo
----
There is a demo deployed in Red Hat Openshift

[Questionnaire] (http://questionnaire-titq.rhcloud.com)

User: demo
Password: demo

Also this presentation in DropBox:

[Demo Presentation] (https://www.dropbox.com/s/q8q83s5hfdwsdz9/survey_app_demo.mkv)

Todos
-----

* Graceful error handle.

* Internal System Admin:
    - System User administration

* Themes manager (right now, use a theme sample)

* Survey users:
    - Allow anonymous users

* Process:
    - Allow conditions in the flow, may be implementing JBoss-Drools

* Statistics:
    - Improve
    - BigData (Storm, Hadoop)
    - R

