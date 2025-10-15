# Orca Photo Share (REDCap External Module) 

As the name implies, this external module pulls approved images/gifs/videos that are shared via REDCap survey and publishes them to a Google Photos Album. 

# Introduction

I tried to do my best to describe the overall process, as I went through it.  Some steps may not match exactly what you experience, but they should be close enough to get you through and complete the setup.

# Requirements

## Linux-based environments

You need to install and enable the following PHP Extension, if not already installed:

- BCMath
 
> **NOTE:** You may need to target your PHP version
> 
> `sudo apt install php8.3-bcmath # Example for PHP 8.3`

This is required by the Google libraries during the upload process.

*Windows-based PHP installs have built-in support, so they don't need the explicit extension to be installed.*

## Having a Google Account!

Ensure you have a Google Account that you have full control of.  With this account, you will need:

- Ability to create and manage a Photos Album
  - The album has to be created by the API code, so no need to create one ahead of time
- Access to https://console.cloud.google.com/
  - This is for configuring the integration between the REDCap EM and the Photos API
- The account must have enough cloud storage space available for the album
  - For reference, The 562 images in the RCC25 album is at most only a few GB

# Setup

## Step 1 - Enabling the Module

Subsequent steps will require information that the module's configuration will provide to you after enabling it in your project.

- After enabling the module, navigate to the "Google Photos Configuration" page and take note of the following values:
   - **Base Domain**
      - This is the base domain of your instance of REDCap
      - **NOTE:** Be sure to verify this value! The code to determine this may not be 100% accurate if you have a very customized domain/URL.
   - **Redirect URI**
      - This is a URL callback to the module that will be used for the initial authorization step (after you've obtained your Client ID and Secret)

## Step 2 - Google Cloud Console

Using the Google account that will host the Photos Album, use the link below to get to the Google Cloud Console.

https://code.google.com/apis/console

### Creating the Project

First you need to Create a Project.  Don't worry about the name too much, but be descriptive, so you know what it means later on.

### Configuring the OAuth Consent

Click "OAuth consent screen" in the left sidebar (you may also see an alert that will take you to the "Configure consent screen").

#### Initial Setup (Branding)

- App Information
   - App name
   - User support email (the email associated with the Google account)
- Audience
   - Select External
   - Contact Info
- Finish!

#### Branding

- Under "Authorized domains", add the REDCap domain that the EM will be hosted on
- Click Save!

#### Audience

- Under "Test users", add your Google email
- Click Save!

#### Clients

Here, we'll click on "Create client"
- Web application
   - Name can stay the default value
- Authorized redirect URIs
   - Grab the "Redirect URI" value from the EM Configuration page.
- Click Create!
- **IMPORTANT!**
   - The "Client ID" and "Client secret" are required for the EM to function.
   - Copy their values into a text document or click the "Download JSON" button.
   - **NOTE:** The Client secret is only available to copy/download for a short period of time

#### Data Access

- Here you'll need to add the scope of authorization that the EM needs to do the integrations
- Using the Scopes from the EM Configuration page, either search the table or just copy/paste them into the "Manually add scopes" section.
- If you manually added, be sure to click "Add to table"
- Click Update!
- Click Save!

#### Photos Library API

Back on your Google Cloud home page, in the "APIs & Services" section, search for the "Photos Library API" and Enable it (if not already enabled by default).

## Step 3 - Client Info & Authorization

Now that we have the Client ID and Secret, we need to provide those values to the module, so it can then trigger the authorization process.

## Step 4 - Album Creation

TODO

---

>  

>  

>  

>  

>  

>  

>  

>  

---