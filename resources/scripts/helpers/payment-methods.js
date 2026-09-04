export function findCashPaymentMethod(paymentMethods = []) {
  return paymentMethods.find(
    (paymentMethod) => paymentMethod.name?.trim().toLocaleLowerCase() === 'cash',
  )
}
