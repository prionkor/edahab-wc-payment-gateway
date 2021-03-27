# WordPress Plugin Scaffold

Easy to start plugin scaffold. 

## How to use

1. Clone this repository 
2. Replace `PLUGIN_NAMESPACE` with your plugin namespace (ie My Cool Plugin `mcp`). For PHP namespace you can use `MCP` and small cases on other occation.
3. Make sure you have composer installed in your machine. Then run `composer dump-autoload`
4. Add all classes under `lib/classes` (follows `psr-4` convension). Composer will automatically find classes. No tedious `requires/includes` are necessery.

### License

GNU GENERAL PUBLIC LICENSE 2+
