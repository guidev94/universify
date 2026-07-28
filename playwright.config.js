import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [["list"]],
  timeout: 60_000,
  expect: { timeout: 10_000 },
  use: {
    baseURL: process.env.BASE_URL || "http://127.0.0.1:4173",
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "off"
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"], viewport: { width: 1440, height: 900 } }
    },
    {
      name: "mobile",
      use: {
        ...devices["Pixel 7"],
        defaultBrowserType: "chromium"
      }
    }
  ]
});
