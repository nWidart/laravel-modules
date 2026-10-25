import fs from 'fs/promises';
import path from 'path';
import { pathToFileURL } from 'url';

async function collectModuleAssetsPaths(paths, modulesPath) {
  modulesPath = path.join(__dirname, modulesPath);

  const moduleStatusesPath = path.join(__dirname, 'modules_statuses.json');

  try {
    // Read module_statuses.json
    const moduleStatusesContent = await fs.readFile(moduleStatusesPath, 'utf-8');
    const moduleStatuses = JSON.parse(moduleStatusesContent);

    // Read module directories
    const moduleDirectories = await fs.readdir(modulesPath);

    for (const moduleDir of moduleDirectories) {
      if (moduleDir === '.DS_Store') {
        // Skip .DS_Store directory
        continue;
      }

      // Check if the module is enabled (status is true)
      if (moduleStatuses[moduleDir] === true) {
        const viteConfigPath = path.join(modulesPath, moduleDir, 'vite.config.js');

        try {
          await fs.access(viteConfigPath);
          // Convert to a file URL for Windows compatibility
          const moduleConfigURL = pathToFileURL(viteConfigPath);

          // Import the module-specific Vite configuration
          const moduleConfig = await import(moduleConfigURL.href);

          if (moduleConfig.paths && Array.isArray(moduleConfig.paths)) {
            paths.push(...moduleConfig.paths);
          }
        } catch (error) {
          // vite.config.js does not exist, skip this module
        }
      }
    }
  } catch (error) {
    console.error(`Error reading module statuses or module configurations: ${error}`);
  }

  return paths;
}

/**
 * Full-reload globs for every module, relative to the app root.
 *
 * Mirrors laravel-vite-plugin's default `refreshPaths`, one directory below
 * `modulesPath`, so Blade, lang, and route changes in modules trigger a
 * reload when Vite runs from the app root.
 *
 * @example
 * import laravel, { refreshPaths } from 'laravel-vite-plugin';
 * import collectModuleAssetsPaths, { moduleRefreshPaths } from './vite-module-loader.js';
 *
 * laravel({ input: inputPaths, refresh: [...refreshPaths, ...moduleRefreshPaths('Modules')] })
 *
 * @param {string} [modulesPath='Modules'] Modules directory, relative to the app root.
 * @returns {string[]}
 */
export function moduleRefreshPaths(modulesPath = 'Modules') {
  const base = modulesPath.replace(/\/+$/, '');

  return [
    'app/Livewire/**',
    'app/View/Components/**',
    'lang/**',
    'resources/lang/**',
    'resources/views/**',
    'routes/**',
  ].map((glob) => `${base}/*/${glob}`);
}

export default collectModuleAssetsPaths;
