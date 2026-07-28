const { test, expect } = require('@playwright/test');

async function makeSubmissionOldEnough(page) {
  await page.locator('input[name="fmpf_started_at"]').evaluate((input) => {
    input.value = String(Math.floor(Date.now() / 1000) - 5);
  });
}

test('English LTR form exposes labels, help, keyboard order, and success state', async ({ page }) => {
  await page.goto('/english-form/');

  const wrapper = page.locator('.fmpf-form-wrap');
  await expect(wrapper).toHaveAttribute('dir', 'ltr');
  await expect(page.getByLabel('Full name (required)')).toBeVisible();
  await expect(page.getByText('Enter your first and last name.')).toBeVisible();

  await page.locator('body').press('Tab');
  await expect(page.getByLabel('Full name (required)')).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(page.getByLabel('Email address (required)')).toBeFocused();

  await page.getByLabel('Full name (required)').fill('Sayid Moghadam');
  await page.getByLabel('Email address (required)').fill('sayid@example.com');
  await page.getByLabel('Topic (required)').selectOption('Project enquiry');
  await page.getByLabel('Message (required)').fill('Browser test submission.');
  await page.getByLabel('Consent (required)').check();
  await makeSubmissionOldEnough(page);
  await page.getByRole('button', { name: 'Send message' }).click();

  await expect(page.getByRole('status')).toContainText('Thank you');
  await expect(page).toHaveURL(/fmpf_status=sent/);
});

test('Server-side errors are summarized, linked, and focused', async ({ page }) => {
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
});

test('Persian RTL form keeps logical direction and accessible group semantics', async ({ page }) => {
  await page.goto('/persian-form/');

  const wrapper = page.locator('.fmpf-form-wrap');
  await expect(wrapper).toHaveAttribute('dir', 'rtl');
  await expect(page.getByLabel('نام و نام خانوادگی (required)')).toBeVisible();

  const group = page.getByRole('group', { name: /روش تماس/ });
  await expect(group).toBeVisible();
  await expect(group.getByLabel('ایمیل')).toBeVisible();
  await expect(group.getByLabel('تلفن')).toBeVisible();

  const computedDirection = await wrapper.evaluate((element) => getComputedStyle(element).direction);
  expect(computedDirection).toBe('rtl');

  await page.locator('body').press('Tab');
  await expect(page.getByLabel('نام و نام خانوادگی (required)')).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(page.getByLabel('ایمیل (required)')).toBeFocused();
});
