import React from 'react';
import cn from '../../utils/classnames';
import styles from './Button.css';

const Button = ({
  className = '',
  children,
  disabled = false,
  loading = false,
  useAnchor = false,
  buttonType = 'normal',
  ...props
}: {
  className?: string;
  children?: any;
  disabled?: boolean;
  loading?: boolean;
  useAnchor?: boolean;
  buttonType?: 'normal' | 'primary' | 'delete';
  [key: string]: any;
}) =>
  React.createElement(
    useAnchor ? 'a' : 'button',
    {
      className: cn(className, styles.button, {
        [styles.buttonPrimary]: buttonType === 'primary',
        [styles.buttonDelete]: buttonType === 'delete',
      }),
      disabled: disabled || loading,
      type: 'button',
      ...props,
    },
    loading ? 'loading..' : children
  );

export default Button;
