import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

// The application root: this file is published next to the root vite.config.js.
const appDirectory = path.dirname(fileURLToPath(import.meta.url));

/**
 * Add the asset entry points of every enabled module to `paths`.
 *
 * Reads the `paths` export of each enabled module's vite.config.js. A module
 * without a vite.config.js is skipped. A module whose vite.config.js fails to
 * load is reported with a warning and skipped; the other modules still load.
 *
 * @example
 * import laravel from 'laravel-vite-plugin';
 * import collectModuleAssetsPaths from './vite-module-loader.js';
 *
 * const inputPaths = await collectModuleAssetsPaths(['resources/css/app.css', 'resources/js/app.js'], 'Modules');
 *
 * laravel({ input: inputPaths, refresh: true })
 *
 * @param {string[]} paths Entry points to add the module entry points to.
 * @param {string} modulesPath Modules directory, relative to the app root.
 * @returns {Promise<string[]>}
 */
async function collectModuleAssetsPaths(paths, modulesPath) {
  modulesPath = path.resolve(appDirectory, modulesPath);

  const moduleStatusesPath = path.join(appDirectory, 'modules_statuses.json');

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
        } catch (error) {
          // vite.config.js does not exist, skip this module
          continue;
        }

        try {
          // Convert to a file URL for Windows compatibility
          const moduleConfigURL = pathToFileURL(viteConfigPath);

          // Import the module-specific Vite configuration
          const moduleConfig = await import(/* @vite-ignore */ moduleConfigURL.href);

          if (moduleConfig.paths && Array.isArray(moduleConfig.paths)) {
            paths.push(...moduleConfig.paths);
          }
        } catch (error) {
          console.warn(`Skipping the assets of module ${moduleDir}: could not load ${viteConfigPath}: ${error?.message ?? error}`);
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
