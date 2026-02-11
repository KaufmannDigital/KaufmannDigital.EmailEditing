# KaufmannDigital.EmailEditing

A Neos CMS package for building and managing responsive emails through the Neos backend. It provides a visual email editor with a component-based approach, using [MJML](https://mjml.io/) under the hood to ensure compatibility across all major email clients.

## Features

- **Visual Email Editing** — Create and edit emails directly in the Neos backend using familiar content elements
- **MJML Rendering** — Emails are rendered via MJML, producing responsive HTML that works across all email clients
- **Reusable Layouts** — Define email layouts with header, footer, and global styling that individual emails inherit
- **Cascading Styles** — Global typography, colors, and spacing can be set on the layout level and overridden per component
- **Content Components** — Section, Column, Headline, Text, Image, Button, Divider, and Spacer
- **Full i18n Support** — All labels available in English and German

## Requirements

- Neos CMS >= 8.3
- PHP >= 8.1
- Node.js (required for MJML compilation)

## Installation

Install via Composer:

```sh
composer require kaufmanndigital/email-editing
```

MJML's Node.js dependencies are automatically installed via a Composer post-install hook.

## Usage

### 1. Create an Email Layout

In the Neos backend, create an **Email Layout** document inside an **Email Folder**. The layout defines:

- **Header & Footer** — Shared content areas rendered on every email using this layout
- **Global Styling** — Default typography (headlines, body text), background colors, button styles, section/column spacing

### 2. Create an Email

Create an **Email** document and select the desired layout. The email's main content area accepts **Sections**, which in turn contain **Columns** with content elements:

```
Email
└── Section
    ├── Column
    │   ├── Headline
    │   ├── Text
    │   └── Button
    └── Column
        └── Image
```

### 3. Available Content Elements

| Element     | Description                                      |
|-------------|--------------------------------------------------|
| **Section** | Container with background image/color support     |
| **Column**  | Layout column within a section                    |
| **Headline**| Heading with full typography control              |
| **Text**    | Rich text with bold and link formatting           |
| **Image**   | Image with optional link and width control        |
| **Button**  | Call-to-action with configurable colors           |
| **Divider** | Horizontal line (solid, dotted, or dashed)        |
| **Spacer**  | Vertical spacing with configurable height         |

### 4. Style Inheritance

Styling follows a cascading pattern: **Layout → Section → Column → Element**. Properties set on the layout (e.g. headline font family) apply globally unless overridden on a specific element.

## Extensions

### CleverReach Integration

The optional [`kaufmanndigital/email-editing-cleverreach`](https://packagist.org/packages/kaufmanndigital/email-editing-cleverreach) package adds the ability to send emails directly to [CleverReach](https://www.cleverreach.com/) from the Neos backend.

```sh
composer require kaufmanndigital/email-editing-cleverreach
```

## CSS Building

```sh
sass --watch DistributionPackages/EmailEditing/Resources/Private/Scss/EmailEditing.scss DistributionPackages/EmailEditing/Resources/Public/Styles/EmailEditing.css
```
