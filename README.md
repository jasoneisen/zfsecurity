Automatically exported from code.google.com/p/zfsecurity

### January 6, 2009: Archived

This project is/was just a proof of concept, a way to demonstrate the "current state" of modularity and distribution within the Zend Framework. I am no longer going to be working on this, as updates to the framework are slowly making this obsolete (like Zend_Tool).

I will leave this up, for archival purposes, so feel free to download / try out the code. It should at least offer a good starting point for those new to zend framework and ACL. But I will not be responding to any bugs or maintaining this project anymore.

Jason

### November 20, 2008: Small Updates

If you update to the newest svn (r138), you will get some new features:

1) Security System updates via migrations. You will need to do this to access the other feature. Go to /security/update in your browser.

2) Custom login/logout routes. Use this if you want users to be routed to the non-default login form, and specify where to drop users off when they've logged out, rather than back at the login screen.

Some other misc issues have been fixed as well. Enjoy!

### October 14, 2008: Screencast

[More info here](https://youtu.be/Mru_Q8W471c)

### September 11, 2008: A blog has formed

A demo app is forming in the repository...

![alt text](https://i.imgur.com/qASfAkw.png "Blog Preview")

### August 25, 2008: First release 0.1 alpha 1!

The module is now in a usable state. Simply check it out to your application/modules folder:

http://zfsecurity.googlecode.com/svn/tags/0.1-ALPHA1/

and point your browser to site.com/security/install. There is a step-by-step installer that will guide you, and you will only need to write a few lines of code to your project.

This provides a full ACL framework, as well as login authorization, group/ user management, and more. All database driven with a friendly UI.

This project assumes only the following:

- You already have a project.
- You are currently using Doctrine.
- You already have an innodb user table.

Check it out and enjoy! Any/all feedback is very welcome.
