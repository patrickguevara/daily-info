# AI Agent Development Guidelines

This document outlines specialized workflows and agent-specific guidelines for AI assistants working on the Daily Info project.

## Overview

Daily Info uses multiple AI integration patterns:
1. **General Development**: Code implementation, refactoring, bug fixes
2. **Testing & QA**: Automated browser testing with Chrome DevTools MCP
3. **Code Review**: Analyzing changes for quality and consistency
4. **Documentation**: Maintaining project documentation

## Chrome DevTools MCP Integration

The Chrome DevTools MCP server enables AI assistants to interact with the application through browser automation. This is essential for QA, testing, and UI verification workflows.

### Prerequisites

1. Chrome DevTools MCP server must be installed and configured
2. Application must be running (`composer dev` or `php artisan serve`)
3. Browser must be accessible to the MCP server

### MCP Tools Available

The Chrome DevTools MCP provides these key tools:

#### Navigation & Page Management
- `list_pages`: List all open browser tabs
- `select_page`: Switch to a specific tab
- `new_page`: Open a new tab with URL
- `close_page`: Close a tab
- `navigate_page`: Navigate to URL, back, forward, or reload

#### Inspection & Capture
- `take_snapshot`: Capture accessibility tree with element UIDs
- `take_screenshot`: Capture visual screenshot (full page or element)
- `list_console_messages`: View browser console logs
- `get_console_message`: Get detailed console message
- `list_network_requests`: View network activity
- `get_network_request`: Get detailed request/response data

#### Interaction
- `click`: Click an element by UID
- `fill`: Fill input/textarea/select by UID
- `fill_form`: Fill multiple form elements at once
- `hover`: Hover over an element
- `press_key`: Send keyboard input
- `drag`: Drag and drop elements
- `handle_dialog`: Handle browser dialogs (alert, confirm, prompt)

#### Testing & Performance
- `wait_for`: Wait for text to appear on page
- `performance_start_trace`: Start performance recording
- `performance_stop_trace`: Stop recording and analyze
- `performance_analyze_insight`: Get detailed performance insights
- `emulate`: Emulate device, network, geolocation, CPU throttling

#### Scripting
- `evaluate_script`: Execute JavaScript in page context

### QA Workflow with Chrome DevTools MCP

#### 1. UI Verification Workflow

Test that the dashboard displays correctly with proper data loading:

```
1. Navigate to the application homepage
   - Use: navigate_page with url="http://localhost:8000"

2. Take a snapshot to verify structure
   - Use: take_snapshot
   - Verify: News cards, weather widgets, stock information present

3. Wait for dynamic content to load
   - Use: wait_for with text matching expected content

4. Take screenshots for visual verification
   - Use: take_screenshot with fullPage=true

5. Check different dates
   - Use: navigate_page with url="http://localhost:8000/dashboard/2024-01-15"
   - Use: take_snapshot to verify date-specific data
```

**Example prompt for AI agent**:
```
"Navigate to the Daily Info dashboard, wait for news to load, take a snapshot to verify all sections are present, then take a screenshot of the full page."
```

#### 2. Responsive Design Testing

Verify the application works across different viewport sizes:

```
1. Resize to mobile viewport
   - Use: resize_page with width=375, height=667

2. Take snapshot and screenshot
   - Verify: Mobile navigation, card layouts, responsive images

3. Resize to tablet viewport
   - Use: resize_page with width=768, height=1024

4. Resize to desktop viewport
   - Use: resize_page with width=1920, height=1080

5. Compare layouts across viewports
```

**Example prompt**:
```
"Test the dashboard responsive design at mobile (375px), tablet (768px), and desktop (1920px) widths. Take screenshots of each and verify the layout adapts correctly."
```

#### 3. Performance Testing Workflow

Analyze application performance and identify bottlenecks:

```
1. Navigate to the dashboard
   - Use: navigate_page

2. Start performance trace
   - Use: performance_start_trace with reload=true, autoStop=false

3. Interact with the application
   - Navigate to different dates
   - Scroll through content

4. Stop trace
   - Use: performance_stop_trace

5. Analyze insights
   - Review Core Web Vitals (LCP, FID, CLS)
   - Identify performance bottlenecks
   - Use: performance_analyze_insight for detailed analysis

6. Report findings with recommendations
```

**Example prompt**:
```
"Run a performance trace on the dashboard while loading news for today. Stop after 10 seconds, analyze the results, and report on Core Web Vitals and any performance issues."
```

#### 4. Network Request Analysis

Verify API calls and data fetching:

```
1. Navigate to dashboard
   - Use: navigate_page

2. List network requests
   - Use: list_network_requests
   - Filter by resourceTypes: ["xhr", "fetch"]

3. Inspect specific requests
   - Use: get_network_request with reqid
   - Verify: Status codes, response times, payload structure

4. Check for errors
   - Use: list_console_messages with types=["error", "warn"]
   - Use: get_console_message for details

5. Report API health
```

**Example prompt**:
```
"Navigate to the dashboard, wait for it to load, then analyze all XHR/fetch requests. Report any failed requests, slow responses (>1s), or console errors."
```

#### 5. Accessibility Audit

Ensure the application is accessible:

```
1. Take verbose snapshot
   - Use: take_snapshot with verbose=true
   - Review: ARIA labels, roles, semantic HTML

2. Check keyboard navigation
   - Use: press_key with "Tab" to navigate
   - Use: take_snapshot after each tab
   - Verify: Focus indicators, logical tab order

3. Verify form accessibility
   - Check labels associated with inputs
   - Verify error messages are announced

4. Test with screen reader simulation
   - Analyze snapshot for proper ARIA usage
   - Ensure content hierarchy is logical
```

**Example prompt**:
```
"Perform an accessibility audit on the dashboard. Take a verbose snapshot, test keyboard navigation by tabbing through interactive elements, and report any accessibility issues."
```

#### 6. User Flow Testing

Test complete user workflows:

```
1. Open application
   - Use: new_page with url

2. Navigate through user flow
   - Example: Home → Select Date → View News → Check Weather

3. At each step:
   - Take snapshot to verify UI state
   - Check console for errors
   - Verify network requests succeeded

4. Test error states
   - Navigate to invalid date
   - Verify error handling

5. Document the flow with screenshots
```

**Example prompt**:
```
"Test the user flow: navigate to dashboard, select yesterday's date, verify news loads, check that weather and stock data are displayed. Document each step with screenshots."
```

#### 7. Form Interaction Testing

Test form submission and validation:

```
1. Navigate to form page
   - Use: navigate_page

2. Take snapshot to identify form elements
   - Note element UIDs

3. Fill form fields
   - Use: fill or fill_form with element UIDs and test data

4. Submit form
   - Use: click on submit button UID

5. Verify results
   - Use: wait_for expected success message
   - Take snapshot to confirm state change
```

**Example prompt**:
```
"Test the date picker form: fill in a date, submit, and verify the dashboard loads data for that date."
```

### Best Practices for MCP Testing

#### Always Take Snapshots Before Screenshots
Snapshots provide the accessibility tree with element UIDs needed for interaction:
```
1. take_snapshot first to get element UIDs
2. Use UIDs for click, fill, hover actions
3. take_screenshot for visual verification
```

#### Wait for Dynamic Content
Use `wait_for` to ensure content loads before assertions:
```javascript
// Wait for news headlines to appear
wait_for({ text: "News", timeout: 5000 })
```

#### Handle Errors Gracefully
Always check console and network for errors:
```
1. list_console_messages with types=["error"]
2. list_network_requests and check for failed responses
3. Report issues with context
```

#### Use Descriptive Reporting
When reporting findings:
- Include screenshots with specific issues highlighted
- Reference element UIDs from snapshots
- Provide reproduction steps
- Suggest fixes when possible

#### Emulate Real Conditions
Test under realistic scenarios:
```javascript
// Test with slow network
emulate({ networkConditions: "Slow 3G" })

// Test with CPU throttling
emulate({ cpuThrottlingRate: 4 })

// Test with geolocation
emulate({ geolocation: { latitude: 37.7749, longitude: -122.4194 } })
```

### Common QA Tasks

#### Task: Verify Dashboard Loads Correctly
```
1. navigate_page to http://localhost:8000
2. wait_for text="News" with timeout=5000
3. take_snapshot to verify structure
4. list_console_messages to check for errors
5. take_screenshot for visual record
```

#### Task: Test Date Navigation
```
1. navigate_page to http://localhost:8000/dashboard/2024-01-15
2. take_snapshot to identify date in UI
3. verify date matches "2024-01-15" in snapshot
4. take_screenshot to document
```

#### Task: Check Mobile Responsiveness
```
1. resize_page to width=375, height=667
2. take_snapshot to verify mobile layout
3. verify sidebar collapses/hamburger menu appears
4. take_screenshot of mobile view
```

#### Task: Analyze Load Performance
```
1. performance_start_trace with reload=true, autoStop=true
2. Wait for trace to complete
3. Review Core Web Vitals scores
4. performance_analyze_insight for specific insights
5. Report on LCP, FID, CLS metrics
```

#### Task: Verify API Integration
```
1. navigate_page to dashboard
2. wait_for data to load
3. list_network_requests with resourceTypes=["fetch", "xhr"]
4. Verify requests to NewsAPI, OpenWeatherMap, Tiingo
5. Check response status codes and timing
```

## Development Agent Workflows

### Code Implementation Agent

**Responsibilities**:
- Implement new features following Laravel/Vue patterns
- Maintain consistency with existing codebase
- Follow service-oriented architecture
- Write tests alongside implementation

**Workflow**:
```
1. Understand requirements from issue/prompt
2. Read related existing code (services, models, controllers)
3. Design solution following project patterns
4. Implement backend (service → controller → route)
5. Implement frontend (component → page → integration)
6. Write Pest tests for new functionality
7. Run tests to verify
8. Format code (Pint, Prettier)
9. Manual QA test with browser (use MCP if needed)
```

**Key principles**:
- Use existing services as templates
- Keep controllers thin, logic in services
- TypeScript for all Vue components
- Test both success and error paths

### Testing Agent

**Responsibilities**:
- Write comprehensive test coverage
- Perform manual QA with Chrome DevTools MCP
- Identify edge cases and error scenarios
- Verify responsive design and accessibility

**Workflow**:
```
1. Review feature implementation
2. Write unit tests for services
3. Write feature tests for controllers
4. Use MCP to perform browser-based QA:
   - Visual verification
   - Interaction testing
   - Performance analysis
   - Accessibility audit
5. Document test results with screenshots
6. Report bugs with reproduction steps
```

**Testing priorities**:
- Service logic (unit tests)
- HTTP responses (feature tests)
- UI rendering (MCP visual testing)
- User flows (MCP interaction testing)
- Performance (MCP tracing)

### Code Review Agent

**Responsibilities**:
- Review pull requests for quality
- Ensure coding standards compliance
- Verify test coverage
- Check for security issues

**Workflow**:
```
1. Review changed files
2. Check for:
   - Laravel/Vue best practices
   - Type safety (PHP types, TypeScript)
   - Error handling
   - Security vulnerabilities
   - Test coverage
   - Code formatting
3. Verify tests pass
4. Use MCP to test UI changes in browser
5. Provide constructive feedback
```

**Review checklist**:
- [ ] Follows existing patterns
- [ ] Services used for business logic
- [ ] Type hints and TypeScript types present
- [ ] Error handling implemented
- [ ] Tests written and passing
- [ ] No security vulnerabilities
- [ ] Code formatted (Pint/Prettier)
- [ ] UI tested in browser (if applicable)

### Documentation Agent

**Responsibilities**:
- Maintain README, CLAUDE.md, AGENTS.md, CONTRIBUTING.md
- Document new features and APIs
- Keep setup instructions current
- Update architecture diagrams

**Workflow**:
```
1. Review code changes
2. Identify documentation needs
3. Update relevant markdown files
4. Add code examples where helpful
5. Verify documentation accuracy by testing steps
6. Ensure consistent formatting
```

## Agent Communication Patterns

### Requesting Browser QA

When development work is complete and needs QA:

```
"I've implemented [feature]. Please use Chrome DevTools MCP to:
1. Navigate to [URL]
2. Verify [specific elements] are present
3. Test [specific interactions]
4. Check for console errors
5. Take screenshots for documentation"
```

### Reporting QA Results

When QA identifies issues:

```
"QA Results for [feature]:

PASSED:
- ✓ Dashboard loads within 2s
- ✓ News cards display correctly
- ✓ Responsive design works on mobile

FAILED:
- ✗ Weather data not showing on 2024-01-15 (see screenshot)
- ✗ Console error: 'undefined location' in OpenWeatherMapService

Reproduction Steps:
1. Navigate to /dashboard/2024-01-15
2. Observe missing weather section
3. Check console (screenshot attached)

Suggestion: Check KeywordMatcherService location extraction logic"
```

### Requesting Code Review

When implementation is ready for review:

```
"[Feature] implementation complete:

Changes:
- Added WeatherDisplayComponent.vue
- Updated DataAggregatorService.php
- Added tests in tests/Feature/WeatherTest.php

Please review:
1. Service pattern usage
2. TypeScript type safety
3. Test coverage
4. UI implementation (use MCP to test)

Tests pass: ✓ (composer test)
Formatted: ✓ (Pint, Prettier)"
```

## Integration with Development Process

### Feature Development Lifecycle

```
1. PLANNING
   - Define requirements
   - Review existing patterns
   - Design solution

2. IMPLEMENTATION (Development Agent)
   - Write backend service/controller
   - Write frontend component/page
   - Write tests

3. TESTING (Testing Agent)
   - Run unit/feature tests
   - Perform MCP browser QA
   - Document results

4. REVIEW (Review Agent)
   - Code quality check
   - Security review
   - UI testing with MCP

5. DOCUMENTATION (Documentation Agent)
   - Update README/docs
   - Add code examples
   - Update changelog

6. DEPLOYMENT
   - Merge to main
   - Deploy to production
   - Monitor for issues
```

### Parallel Agent Workflows

Multiple agents can work simultaneously:

```
Development Agent:
├─ Implements new feature
└─ Writes tests

Testing Agent (parallel):
├─ Tests existing features with MCP
├─ Writes additional test cases
└─ Performs accessibility audit

Documentation Agent (parallel):
├─ Updates API documentation
├─ Adds usage examples
└─ Updates changelog
```

## MCP Tool Reference

### Essential Tools for QA

| Tool | Purpose | When to Use |
|------|---------|-------------|
| `take_snapshot` | Get page structure & element UIDs | Before interactions, verify elements |
| `take_screenshot` | Visual capture | Document UI state, bugs, responsive design |
| `navigate_page` | Go to URL, back, forward, reload | Start tests, change pages |
| `click` | Click element by UID | Test interactions, submit forms |
| `fill` | Fill input/select | Test forms |
| `wait_for` | Wait for text/element | Ensure dynamic content loads |
| `list_console_messages` | Check console logs | Find errors, warnings |
| `list_network_requests` | View API calls | Debug data fetching |
| `performance_start_trace` | Begin performance recording | Analyze speed, Core Web Vitals |
| `resize_page` | Change viewport size | Test responsive design |
| `emulate` | Simulate conditions | Test under slow network, mobile |

### Tool Combination Patterns

**Pattern: Verify Element and Interact**
```
1. take_snapshot → get element UID
2. click(uid) → interact with element
3. wait_for → confirm result
4. take_screenshot → document outcome
```

**Pattern: Performance Analysis**
```
1. navigate_page → load page
2. performance_start_trace(reload=true)
3. wait_for → ensure load complete
4. performance_stop_trace
5. performance_analyze_insight → detailed metrics
```

**Pattern: Debug API Issue**
```
1. navigate_page → trigger data fetch
2. list_network_requests → find API calls
3. get_network_request(reqid) → inspect details
4. list_console_messages → check errors
5. Report findings with context
```

## Conclusion

Effective AI agent collaboration on Daily Info requires:

1. **Clear role definition**: Know which agent handles what
2. **MCP proficiency**: Use Chrome DevTools MCP for comprehensive QA
3. **Pattern consistency**: Follow established workflows
4. **Communication**: Clear handoffs between agents
5. **Quality focus**: Test thoroughly before marking complete

By following these guidelines, AI agents can efficiently develop, test, review, and document Daily Info features while maintaining high code quality and user experience standards.
