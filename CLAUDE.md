# Collaborative Travel Journal - Information Architecture

## Core Entities

### Trip
Primary container for organizing travel experiences.

**Properties:**
- Title/Name
- Description
- Start/End dates
- Status (planning, active, completed)
- Privacy (private by default, public sharing in future)
- Owner (WordPress user)

**Relationships:**
- Has many Stops (ordered)
- Has many Collaborators (WordPress users with roles)

**Collaboration Roles:**
- Owner (full control)
- Contributor (can add/edit content)
- Viewer (read-only)

---

### Stop
A location visited or planned to be visited during travel. Stops can exist independently or as part of a Trip.

**Status:**
- Future (planned Stop in a Trip) - automatically becomes Past when date passes
- Past (visited Stop in a Trip)
- Bookmark (personal saved location, not yet in a Trip)
- Suggestion (shared recommendation, discoverable by other users)

**Type:**
- Location (generic/default)
- Attraction
- Accommodation
- Restaurant
- Activity
- Transportation Hub
- Custom types (extensible)

**Location Fields:**
- Name (optional) - custom name for the stop
- Place (City, State/Province, Zip/Postal Code, Country)
- Latitude (decimal degrees)
- Longitude (decimal degrees)

**Properties:**
- Dates/times (arrival, departure)
- Order/sequence within Trip
- Can be favorited by users

**Relationships:**
- Belongs to Trip (optional - Bookmarks and Suggestions exist independently)
- Has many Entries (unified content type, multiple per Stop)
- Has many Media items
- Has many Contributors
- Can be converted from Bookmark/Suggestion to Trip Stop

**Content Types:**
- Entries (unified rich text content - can have multiple)
- Photos and videos
- Audio recordings
- Links (restaurants, attractions, etc.)
- Expenses/budget items

---

### Media
Photos, videos, audio recordings attached to Stops. Organized by Stop only (no independent albums/galleries).

**Properties:**
- File/URL
- Type (photo/video/audio)
- Caption/description
- Timestamp/date taken
- Location (if different from Stop)
- Uploaded by (user)
- Can be favorited by users

---

### Entry
Written content associated with a Stop. Multiple entries can exist per Stop, allowing for ongoing journaling.

**Properties:**
- Content (rich text)
- Author (user)
- Created/updated timestamps
- Title (optional)
- Mood/weather/conditions (optional metadata)
- Can be favorited by users

---

### Expense
Budget tracking for Stops.

**Properties:**
- Amount
- Currency
- Category (food, lodging, transport, activities, etc.)
- Description
- Date
- Paid by (user)

---

### Song
Music associated with a Trip. Every Trip has a playlist of Songs, creating a soundtrack for the journey.

**Properties:**
- Title
- Artist
- Album (optional)
- Date/time played
- Added by (user)
- External link/identifier (Spotify, Apple Music, YouTube, etc.)
- Notes (optional - why this song, what memory it evokes)

**Relationships:**
- Belongs to Trip
- Songs can appear multiple times in a Trip's playlist (different dates played)

**Notes:**
- A Trip's playlist is chronological based on date played
- Same song can be added multiple times with different timestamps

---

## Decisions Made

### Data Model
✓ Stops have types (Location as default, plus Attraction, Accommodation, Restaurant, Activity, Transportation Hub)
✓ Stop status: Future, Past, Bookmark (personal), Suggestion (shared/discoverable)
✓ Location data: Name (optional), Place (City/State/Zip/Country), Latitude, Longitude
✓ Unified Entry content type, multiple entries per Stop allowed
✓ Media organized by Stop only (no independent albums/galleries)
✓ Favorites can be applied to Stops, Entries, and Media
✓ Songs entity: Trips have playlists, songs can repeat with different timestamps
✓ Checklists and reservations deferred to future

### Authentication & Privacy
✓ Shareable link invitations that require email to finish account creation
✓ Passwordless login (email-only authentication)
✓ Integrates with WordPress authentication (users are WordPress Author-level minimum)
✓ No private content within a Trip - all collaborators see everything
✓ Only Trips can be private (not visible to non-authenticated users)

### Discovery (Suggestions)
✓ Search/browse interface for Suggestions (shared Stops)
✓ Filter by location, type, tags
✓ User ratings or popularity for Suggestions

## Questions for Next Phase

### Data Model Details
1. Should Links be their own entity or just embedded in Entry content?
2. Tags/categories - separate entity or just taxonomy?
3. Should there be a featured/cover photo for each Stop or Trip?
4. Ratings for Suggestions - simple thumbs up/down or 5-star scale?
5. Should Songs support playlist ordering beyond chronological (manual reordering)?

### Trip Organization
6. Can a Trip have nested structure (e.g., multi-country trip with regional segments)?
7. Should there be templates for common trip types?
8. How should Trip status transitions work (planning → active → completed)?
9. Should Trip dates be derived from Stop dates, or independent?

### User Experience & Interface
10. Map visualization - primary interface or supplementary?
11. Timeline view for Trips - how should this work?
12. How are Favorites displayed/accessed (dedicated view, filtering)?
13. Playlist player - just links to external services or embedded playback?
14. Should there be export/sharing features (PDF trip book, social media)?

### Technical Considerations
15. Geocoding - automatic from Place field or manual entry only?
16. Media storage - WordPress media library or separate?
17. Real-time collaboration or just async updates?
18. Mobile app considerations for the architecture?
