# Postman MCP Server (setup)

This repo includes a Blackbox MCP configuration for the Postman MCP server.

## Configuration file
- `blackbox_mcp_settings.json`

## Server name
- `github.com/postmanlabs/postman-mcp-server`

## Running the local MCP server
The Postman MCP server may require authentication depending on version/region.

If you have an API key, set it before running:

```bat
set POSTMAN_API_KEY=your_key_here
npx -y @postman/postman-mcp-server --minimal
```

Then connect via Blackbox MCP using the `blackbox_mcp_settings.json` entry.

