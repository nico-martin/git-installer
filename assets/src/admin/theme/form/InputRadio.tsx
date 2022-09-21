import React from 'react';
import { UseFormRegister } from 'react-hook-form';
import cn from '../../utils/classnames';
import styles from './InputRadio.css';

const InputRadio = React.forwardRef<
  HTMLInputElement,
  {
    name: string;
    value?: string;
    className?: string;
    classNameInput?: string;
    options: Record<string, string>;
    optionProps?: (value: string, label: string) => Record<string, any>;
    onChange?: (checked: string) => void;
  } & ReturnType<UseFormRegister<any>>
>(
  (
    {
      name,
      value = '',
      className = '',
      classNameInput = '',
      options = {},
      optionProps = () => ({}),
      onChange = () => ({}),
    },
    ref
  ) => (
    <div className={cn(className, styles.input)}>
      {Object.entries(options).map(([optionValue, optionLabel]) => (
        <div className={styles.element} key={optionValue}>
          <input
            type="radio"
            className={styles.input}
            id={`${name}-${optionValue}`}
            name={name}
            value={optionValue}
            defaultChecked={value === optionValue}
            ref={ref}
            onChange={(e) => onChange(optionValue)}
            {...optionProps(optionValue, optionLabel)}
          />
          <label className={styles.label} htmlFor={`${name}-${optionValue}`}>
            {optionLabel}
          </label>
        </div>
      ))}
    </div>
  )
);

export default InputRadio;
