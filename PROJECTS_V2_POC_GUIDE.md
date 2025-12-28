# GitHub Projects V2 POC - Implementation Guide

## Overview

This POC adds the ability to:
1. **Add new items** to a GitHub Projects V2 board
2. **Update item columns** (Status field) on the board directly from the UI
3. **Add existing issues/PRs** to projects from the issue/PR detail view (like GitHub.com)

## What Was Implemented

### Backend (Laravel)

#### New Endpoints

1. **POST `/api/{organization}/{repository}/projects/{number}/items`**
   - Route name: `organizations.repositories.project.item.add`
   - Adds a new draft item to a GitHub Project V2 board
   - Required payload:
     ```json
     {
       "projectId": "PVT_...", // GitHub Project ID
       "title": "Item Title"
     }
     ```
   - Returns: `{ success: true, itemId: "...", message: "..." }`

2. **GET `/api/{organization}/{repository}/projects/{number}/fields`**
   - Route name: `organizations.repositories.project.fields`
   - Fetches the Status field and available options for a project
   - Returns: `{ projectId: "PVT_...", field: { id: "PVTF_...", name: "Status", options: [...] } }`
   - Used to populate the status dropdown when adding items

3. **PATCH `/api/{organization}/{repository}/projects/{number}/items/{itemId}`**
   - Route name: `organizations.repositories.project.item.update`
   - Updates a project item's field value (e.g., Status column)
   - Required payload:
     ```json
     {
       "projectId": "PVT_...",
       "itemId": "PVTDI_...",
       "fieldId": "PVTF_...",
       "value": "Todo" // The column/status option ID
     }
     ```
   - Returns: `{ success: true, message: "..." }`

#### Implementation Files Modified

- **`app/Http/Controllers/RepositoryController.php`**
  - Added `addProjectItem()` method - Creates draft items
  - Added `updateProjectItemField()` method - Updates item columns
  - Added `getProjectFields()` method - Fetches project Status field options (new!)
  - Added `addItemToProject()` method - Adds existing issues/PRs to projects AND sets their status field (new!)
  - All use GitHub's GraphQL API to interact with Projects V2

- **`app/Http/Controllers/ItemController.php`**
  - Modified `show()` method (line 170-222) - Now includes `node_id` for items
  - Queries GitHub GraphQL API to get the global node ID for each issue/PR

- **`routes/api.php`**
  - Added GET route for fetching project fields (line 31-32) (new!)
  - Added POST route for adding items (line 34-35)
  - Added PATCH route for updating items (line 37-38)
  - Added POST route for adding existing items to projects (line 40-41)

### Frontend (Svelte)

#### New UI Components in Project.svelte

1. **Add Item Form**
   - Button to toggle the form visibility
   - Text input for item title
   - Text input for Project ID
   - Add/Cancel buttons
   - Helper text explaining where to get the Project ID

2. **Project Metadata Section**
   - Display and input fields for Project ID
   - Input field for Field ID (Status field)
   - Visible when either ID is set

3. **Column Update Buttons**
   - For each item on the board, displays buttons to move it to other columns
   - Only visible when both Project ID and Field ID are set
   - Format: `→ [Column Name]`
   - Clicking moves the item to that column and refreshes the board

#### Implementation Files Modified

- **`resources/js/components/Project.svelte`**
  - Added state variables: `showAddForm`, `newItemTitle`, `projectId`, `fieldId`, `isSubmitting`
  - Added `handleAddItem()` function to create items (line 31-66)
  - Added `handleUpdateColumn()` function to move items (line 68-104)
  - Added new UI sections (line 124-214)

- **`resources/js/components/item/Sidebar.svelte`**
  - Added state variables: `projects`, `projectsWithFields`, `selectedProjectForAdd`, `selectedStatus`
  - Added `handleSelectProjectToAdd()` function - Fetches project field options (new!)
  - Added `handleAddToProjectWithStatus()` function - Adds item with selected status (new!)
  - Added `cancelSelectingStatus()` function - Cancels the status selection
  - Fetches available projects on component mount
  - Added "Projects" sidebar section with:
    - List of projects with "Add" buttons
    - Status selector dropdown when a project is selected (new!)
    - Shows loading/adding/success states with appropriate messaging

## How to Use

### Step 1: Get Your Project IDs from GitHub

You need two IDs from GitHub:

#### Project ID
1. Go to your GitHub project in Projects V2
2. Open your browser's DevTools (F12) → Network tab
3. Refresh the page
4. Look for any GraphQL requests to `api.github.com/graphql`
5. In the response, find the `projectV2` object and copy its `id` field
   - Format: `PVT_kwDOA...` (starts with PVT)

**Alternative: Use GitHub GraphQL Explorer**
1. Go to https://docs.github.com/en/graphql/overview/explorer
2. Run this query:
   ```graphql
   query {
     organization(login: "YOUR_ORG") {
       projectV2(number: PROJECT_NUMBER) {
         id
       }
     }
   }
   ```

#### Field ID (Status field)
1. Use the same GraphQL explorer
2. Run this query:
   ```graphql
   query {
     organization(login: "YOUR_ORG") {
       projectV2(number: PROJECT_NUMBER) {
         field(name: "Status") {
           ... on ProjectV2SingleSelectField {
             id
             options {
               id
               name
             }
           }
         }
       }
     }
   }
   ```
3. Copy the `id` from the field object (format: `PVTF_...`)

### Step 2: Use the UI

1. Navigate to your project board in the app
2. Click the **"+ Add Item"** button
3. Fill in:
   - **Item Title**: The name of the new item
   - **Project ID**: Paste your project ID
4. Click **"Add"**
5. The page will refresh and show the new item

### Step 3: Update Item Columns (Move Items)

1. Enter your **Project ID** and **Field ID** in the metadata section
   - The section appears when you add the Project ID in the form
2. Below each item, you'll see buttons for each available column
3. Click the arrow button to move the item to that column
4. The board will refresh automatically

### Step 4: Add Issue/PR to Projects with Status (From Item View)

1. Open any issue or pull request (e.g., `http://localhost:8000/#/org/repo/issues/77`)
2. In the right sidebar, you'll see a **"Projects"** section with all available projects
3. Click **"+ Add to [Project Name]"** to add the issue/PR to that project
4. A **status selector** will appear with:
   - The project name
   - A dropdown to select which column/status to add it to
   - "Add" and "Cancel" buttons
5. Select the desired status from the dropdown
6. Click **"Add"** to add the item to the project in that column
7. The button will show:
   - **"Loading..."** while fetching project fields
   - **"Adding..."** while adding the item
   - **"✓ Added!"** temporarily after success
   - Then return to normal after 2 seconds

**Key feature**: When you add an item to a project from the issue/PR view, it automatically gets assigned to the status column you select!

This works just like on GitHub.com!

## Technical Details

### GitHub GraphQL Mutations Used

The POC uses these GitHub GraphQL mutations:

1. **`projectsV2AddDraftItem`** - Creates a draft item (no content, just a board item)
   ```graphql
   mutation ($input: ProjectsV2AddDraftItemInput!) {
     projectsV2AddDraftItem(input: $input) {
       item {
         id
       }
     }
   }
   ```

2. **`projectsV2ItemFieldValueUpdate`** - Updates item field values
   ```graphql
   mutation ($input: ProjectsV2ItemFieldValueUpdateInput!) {
     projectsV2ItemFieldValueUpdate(input: $input) {
       projectsV2item {
         id
       }
     }
   }
   ```

3. **`addProjectV2ItemById`** - Adds existing issues/PRs to a project
   ```graphql
   mutation ($projectId: ID!, $contentId: ID!) {
     addProjectV2ItemById(input: {projectId: $projectId, contentId: $contentId}) {
       item {
         id
       }
     }
   }
   ```
   - Requires the global node ID of the issue/PR (`contentId`)
   - This is fetched from GitHub in the item detail endpoint

4. **`updateProjectV2ItemFieldValue`** - Updates a field value on a project item (correct mutation for status updates)
   ```graphql
   mutation ($input: UpdateProjectV2ItemFieldValueInput!) {
     updateProjectV2ItemFieldValue(input: $input) {
       projectV2Item {
         id
       }
     }
   }
   ```
   - Used for updating status/column of items on the board
   - Input structure:
     ```json
     {
       "projectId": "PVT_...",
       "itemId": "PVTI_...",
       "fieldId": "PVTSSF_...",
       "value": {
         "singleSelectOptionId": "option_id"
       }
     }
     ```

### API Authentication

Both endpoints use the GitHub API token configured in:
- **Backend**: `config/services.github.access_token` (from `.env`)
- **API Version**: `2022-11-28` (configured in `ApiHelper.php`)

## Current Limitations & Future Improvements

### Current Limitations

1. **Manual ID Input**: Project ID and Field ID must be manually entered
   - Could be auto-fetched from GitHub GraphQL API

2. **Draft Items Only**: The `projectsV2AddDraftItem` creates draft items
   - Could be enhanced to link existing issues/PRs instead

3. **Status Field Only**: Updates are hardcoded for the "Status" field
   - Could support any custom project field

4. **No Drag-and-Drop**: Column updates require clicking buttons
   - Could add drag-and-drop functionality

5. **Simple UI**: Using inline styles and basic forms
   - Could be improved with proper Svelte components

### Potential Next Steps

1. **Auto-fetch Project Metadata**
   - Query GitHub API to get project ID from the URL/context
   - Auto-fetch available fields and options

2. **Link Existing Issues/PRs**
   - Instead of creating draft items, allow adding existing issues/PRs
   - Use `projectsV2AddItemById` mutation

3. **Drag-and-Drop Support**
   - Use Svelte drag-and-drop libraries
   - Provide more intuitive UI

4. **Batch Operations**
   - Allow updating multiple items at once
   - Support for bulk field updates

5. **Field Value Visualization**
   - Show actual option IDs alongside names
   - Better UI for selecting target columns

6. **Error Handling**
   - More detailed error messages from GitHub API
   - Validation of IDs before making requests

## Testing the POC

### Prerequisites
- You have a GitHub Projects V2 board
- You have a GitHub personal access token with `repo` and `projects` scopes
- You've configured it in your `.env` file

### Test Flow

1. Open the projects page in your app
2. Click on a project to view the board
3. Click "+ Add Item" and enter a test item title
4. Paste your Project ID and click Add
5. Verify the item appears on the board
6. Enter your Field ID in the metadata section
7. Click one of the column buttons to move the item
8. Verify the item moves to the new column

## Troubleshooting

### "Failed to add item" error
- Verify the Project ID is correct (should start with `PVT_`)
- Check that your GitHub token has `projects` scope
- Check browser console for detailed error messages

### Column update buttons don't appear
- Make sure you've entered the Project ID first
- Then enter the Field ID
- The buttons should appear below each item

### "Project info not available" when trying to update
- Field ID is missing - enter it in the metadata section
- Check that you're using the correct Field ID (should start with `PVTF_`)

## Files Changed

```
Modified:
- app/Http/Controllers/RepositoryController.php (added 2 methods)
- app/Http/Controllers/ItemController.php (updated show method)
- routes/api.php (added 2 routes)
- resources/js/components/Project.svelte (added UI and handlers)
- resources/js/components/item/Sidebar.svelte (added Projects section)

Created:
- This documentation file (PROJECTS_V2_POC_GUIDE.md)
```

## How It Works: Add Item with Status

When you add an issue/PR to a project from the sidebar:

1. **Frontend loads projects** - When you open an issue/PR, it fetches available projects for the repo
2. **You click "Add to [Project]"** - Frontend fetches the project's Status field options via `getProjectFields`
3. **You select a status** - The dropdown shows all available status columns (Todo, In Progress, Done, etc.)
4. **You click "Add"** - Frontend sends:
   - `projectId`: The project's ID
   - `contentId`: The issue/PR's global node ID
   - `fieldId`: The Status field ID
   - `statusValue`: The selected status option ID
5. **Backend adds and sets status** - The `addItemToProject` method:
   - Calls `addProjectV2ItemById` to add the item to the project
   - Then calls `projectsV2ItemFieldValueUpdate` to set the status field
6. **Item appears on board** - The item is now in the project with the selected status column

This is a two-step GraphQL operation (add, then set status) to ensure the item is properly initialized.

### Correct Mutation Names

Make sure you're using the correct mutation names for GitHub Projects V2:
- ✅ `addProjectV2ItemById` - Add an issue/PR to a project
- ✅ `updateProjectV2ItemFieldValue` - Update a field on an item (status/column)
- ❌ `projectsV2ItemFieldValueUpdate` - This does NOT exist (incorrect naming)
- ❌ `projectsV2AddItemById` - This does NOT exist (incorrect naming)

If you get an "undefined field" error, verify you're using the correct mutation names above.

## How Node IDs Work

The `node_id` (global node ID) is GitHub's internal identifier for any object in GraphQL. For issues/PRs:

- **Obtained from**: The item detail endpoint automatically fetches it from GitHub's GraphQL API
- **Format**: Starts with `MDU:Issue` (for issues) or `PR` (for pull requests), encoded in base64
- **Used for**: The `addProjectV2ItemById` mutation to link existing issues/PRs to projects
- **Transparent to user**: You don't need to manually get this - it's fetched automatically in the sidebar component

## API Response Examples

### Add Item Success
```json
{
  "success": true,
  "itemId": "PVTDI_kwDOA...",
  "message": "Item added successfully"
}
```

### Update Field Success
```json
{
  "success": true,
  "message": "Item field updated successfully"
}
```

### Add Item to Project Success
```json
{
  "success": true,
  "itemId": "PVTDI_kwDOA...",
  "message": "Added to project successfully"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Failed to add item",
  "errors": [
    {
      "message": "Could not resolve to a ProjectsV2 with the ID of 'invalid_id'"
    }
  ]
}
```
