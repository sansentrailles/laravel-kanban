// Единый способ сообщить, что фича ещё не реализована: кнопка остаётся в UI,
// но клик не падает с ReferenceError и не выглядит молча сломанным.
// ToastContainer вешает window.toast при монтировании; optional chaining
// защищает от вызова до этого момента.
export const notifyNotImplemented = (message = 'Функция пока не реализована') => {
  window.toast?.info(message)
}
