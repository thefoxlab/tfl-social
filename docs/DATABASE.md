# TFL Social Data Model

## Overview

TFL Social stores normalized social media data independent of the provider.

The application should never interact with provider-specific data structures.

---

# Entities

## Account

Represents the owner of one or more social connections.

Examples:

- Lodge
- Hotel
- Company
- Website
- User

---

## Connection

Represents a connected social media account.

Examples:

- Facebook Page
- Instagram Business Account

Each Account may have multiple Connections.

---

## Post

Represents a normalized social media post.

A Post belongs to one Connection.

---

## Media

Represents one or more media files attached to a Post.

Examples:

- Image
- Video
- Carousel

---

## Sync

Stores synchronization history.

---

# Relationships

Account

↓

Connection

↓

Post

↓

Media

---

# Physical Tables

## social_account

Stores logical accounts.

---

## social_connection

Stores provider connections.

---

## social_post

Stores normalized posts.

---

## social_media

Stores media attached to posts.

---

## social_sync

Stores synchronization history.

---

# Provider Independence

The database must never contain provider-specific tables.

Avoid tables such as:

- facebook_post
- instagram_post

Every provider maps into the same schema.

---

# Multiple Accounts

One Account

↓

Many Connections

↓

Many Posts

---

# Multiple Providers

One Account may connect:

- Facebook
- Instagram

Future:

- YouTube
- LinkedIn
- TikTok

---

# Data Ownership

The package should remain application independent.

Applications decide what an Account represents.

Examples:

- Lodge
- Company
- Website
- Customer

The package only stores normalized social data.

---

# Storage Principles

Store normalized data.

Store provider response as raw JSON for debugging.

Never expose raw provider responses to application code.

---

# Indexing

Future indexes should exist on:

- provider
- account
- published_at
- external_id
- status

---

# Future Support

The model should support additional providers without requiring schema redesign.