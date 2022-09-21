import React from 'react';
import { UseFormRegister } from 'react-hook-form';
import cn from '../../utils/classnames';

const InputCheckbox = React.forwardRef<
  HTMLInputElement,
  {
    name: string;
    value?: boolean;
    className?: string;
    classNameInput?: string;
    onChange?: (checked: boolean) => void;
    defaultChecked;
  } & ReturnType<UseFormRegister<any>>
>(
  (
    {
      name,
      value = false,
      className = '',
      classNameInput = '',
      onChange = () => ({}),
    },
    ref
  ) => {
    const [checked, setChecked] = React.useState<boolean>(value);

    React.useEffect(() => {
      if (onChange) {
        onChange(checked);
      }
    }, [checked]);

    return (
      <div className={cn(className)}>
        <input
          id={name}
          name={name}
          className={cn(classNameInput)}
          type="checkbox"
          ref={ref}
          value={value ? 'true' : 'false'}
          checked={checked}
          onChange={(e) => setChecked(e.target.checked)}
        />
      </div>
    );
  }
);

export default InputCheckbox;
