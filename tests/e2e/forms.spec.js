const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

async function makeSubmissionOldEnough(page) {
  await page.locator('input[name="fmpf_started_at"]').evaluate((input) => {
    input.value = String(Math.floor(Date.now() / 1000) - 5);
  });
}

async function expectNoSeriousAccessibilityViolations(page, includeSelector) {
  const builder = new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .disableRules(['color-contrast']);

  if (includeSelector) {
    builder.include(includeSelector);
  }

  const results = await builder.analyze();
  const blockingViolations = results.violations.filter((violation) =>
    ['serious', 'critical'].includes(violation.impact)
  );

  expect(blockingViolations, JSON.stringify(blockingViolations, null, 2)).toEqual([]);
}

test('English LTR form exposes labels, help, keyboard order, and success state', async ({ page }) => {
  await page.goto('/english-form/');

  const wrapper = page.locator('.fmpf-form-wrap');
  const nameInput = page.getByLabel('Full name (required)');
  const emailInput = page.getByLabel('Email address (required)');

  await expect(wrapper).toHaveAttribute('dir', 'ltr');
  await expect(nameInput).toBeVisible();
  await expect(page.getByText('Enter your first and last name.')).toBeVisible();
  await expectNoSeriousAccessibilityViolations(page, '.fmpf-form-wrap');

  await nameInput.focus();
  await page.keyboard.press('Tab');
  await expect(emailInput).toBeFocused();

  await nameInput.fill('Sayid Moghadam');
  await emailInput.fill('sayid@example.com');
  await page.getByLabel('Topic (required)').selectOption('Project enquiry');
  await page.getByLabel('Message (required)').fill('Browser test submission.');
  await page.getByRole('checkbox', { name: 'Yes' }).check();
  await makeSubmissionOldEnough(page);
  await page.getByRole('button', { name: 'Send message' }).click();

  await expect(page.getByRole('status')).toContainText('Thank you');
  await expect(page).not.toHaveURL(/fmpf_status=/);
  await expectNoSeriousAccessibilityViolations(page, '.fmpf-form-wrap');
});

test('Server-side errors are summarized, linked, focused, and exposed accessibly', async ({ page }) => {
  await page.goto('/english-form/');

  await page.locator('form.fmpf-form').evaluate((form) => {
    form.noValidate = true;
  });
  await makeSubmissionOldEnough(page);
  await page.getByRole('button', { name: 'Send message' }).click();

  const alert = page.getByRole('alert');
  await expect(alert).toBeVisible();
  await expect(alert).toContainText('Please review');
  await expect(alert.getByRole('link')).toHaveCount(5);

  const nameInput = page.getByLabel('Full name (required)');
  await expect(nameInput).toHaveAttribute('aria-invalid', 'true');
  await expect(nameInput).toBeFocused();
  await expect(page).not.toHaveURL(/fmpf_state=/);
  await expectNoSeriousAccessibilityViolations(page, '.fmpf-form-wrap');
});

test('Persian RTL form keeps logical direction and accessible group semantics', async ({ page }) => {
  await page.goto('/persian-form/');

  const wrapper = page.locator('.fmpf-form-wrap');
  const nameInput = page.getByLabel('نام و نام خانوادگی (required)');
  const emailInput = page.getByLabel('ایمیل (required)');

  await expect(wrapper).toHaveAttribute('dir', 'rtl');
  await expect(nameInput).toBeVisible();

  const group = page.getByRole('group', { name: /روش تماس/ });
  await expect(group).toBeVisible();
  await expect(group.getByLabel('ایمیل')).toBeVisible();
  await expect(group.getByLabel('تلفن')).toBeVisible();

  const computedDirection = await wrapper.evaluate((element) => getComputedStyle(element).direction);
  expect(computedDirection).toBe('rtl');
  await expectNoSeriousAccessibilityViolations(page, '.fmpf-form-wrap');

  await nameInput.focus();
  await page.keyboard.press('Tab');
  await expect(emailInput).toBeFocused();
});
