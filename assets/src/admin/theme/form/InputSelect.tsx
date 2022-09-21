import React from 'react';
import { UseFormRegister } from 'react-hook-form';
import cn from '../../utils/classnames';
import { ISetting } from '../../utils/types';

const InputSelect = React.forwardRef<
  HTMLSelectElement,
  {
    name: string;
    value?: string;
    className?: string;
    classNameInput?: string;
    options: Record<string, string>;
    optionProps?: (value: string, label: string) => Record<string, any>;
    emptyOption?: boolean;
    setting?: ISetting;
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
      emptyOption = false,
      setting,
      ...props
    },
    ref
  ) => {
    const settingsOptions = options || setting.values;
    return (
      <div className={cn(className)}>
        <select
          value={value}
          id={name}
          name={name}
          className={cn(classNameInput)}
          ref={ref}
          {...props}
        >
          {emptyOption && <option value="" {...optionProps('', '')} />}
          {Object.entries(settingsOptions || {}).map(([value, label]) => (
            <option
              value={value}
              key={value}
              {...optionProps(value, String(label))}
            >
              {label}
            </option>
          ))}
        </select>
      </div>
    );
  }
);

export default InputSelect;
