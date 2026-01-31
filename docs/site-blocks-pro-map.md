---
title: "Map Block"
slug: "block-pro-map"
audience: "site-owner"
category: "blocks"
order: 28
---

# Map Block

> Interactive maps with support for OpenStreetMap, Google Maps, and Mapbox.

---

## Overview

The Map block embeds interactive maps to display your business location, event venue, or any geographic point of interest. It supports three map providers including a free option (OpenStreetMap) that requires no API key, plus premium options from Google Maps and Mapbox.

---

## Fields

| Field | Description |
|-------|-------------|
| **Heading** | Optional section title above the map |
| **Subheading** | Supporting text below the heading |
| **Latitude** | Location latitude coordinate (required) |
| **Longitude** | Location longitude coordinate (required) |
| **Address** | Address text for marker popup |
| **Marker Title** | Tooltip text on marker hover |
| **Contact Info** | Additional information displayed below the map |
| **Provider** | Map service: OpenStreetMap, Google, or Mapbox |
| **Zoom** | Map zoom level (1-20) |
| **Height** | Map height: Small, Medium, Large, or XL |
| **Style** | Map appearance (provider-dependent) |
| **Show Marker** | Display location marker pin |
| **Scrollwheel Zoom** | Enable zoom via mouse scroll |
| **Rounded** | Apply rounded corners |
| **Content Width** | Control the maximum width of the content area |
| **Animation** | Entrance animation effect |

---

## Map Providers

| Provider | API Key | Best For |
|----------|---------|----------|
| **OpenStreetMap** | Not required | Free option, simple maps |
| **Google Maps** | Required | Familiar interface, Street View |
| **Mapbox** | Required | Custom styling, high performance |

---

## Map Styles

Available for Google Maps and Mapbox:

| Style | Description |
|-------|-------------|
| **Streets** | Standard road map view |
| **Satellite** | Aerial/satellite imagery |
| **Hybrid** | Satellite with road labels |
| **Terrain** | Topographic features |

Note: OpenStreetMap uses the streets style only.

---

## Height Options

| Option | Size |
|--------|------|
| **Small** | 300px |
| **Medium** | 400px |
| **Large** | 500px |
| **XL** | 600px |

---

## Finding Coordinates

To find latitude and longitude for a location:
1. Go to Google Maps
2. Right-click on the desired location
3. Click the coordinates to copy them
4. Paste latitude and longitude into the block fields

---

## Tips

- OpenStreetMap is a great free option for simple location display
- Disable scrollwheel zoom if the map is near scrollable content
- Add contact info to display phone, email, or hours below the map
- Zoom level 14-16 works well for street-level views

---

## Examples

<!-- Examples will be added as blocks below -->
